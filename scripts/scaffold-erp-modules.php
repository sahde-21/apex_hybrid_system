<?php

/**
 * SCF ERP Module Scaffolder — generates migrations, models, enums, factories,
 * repositories, services, policies, form requests, controllers, routes, and Livewire pages.
 */

declare(strict_types=1);

require __DIR__.'/../vendor/autoload.php';

$app = require_once __DIR__.'/../bootstrap/app.php';
$app->make(Kernel::class)->bootstrap();

use Illuminate\Contracts\Console\Kernel;
use Illuminate\Support\Str;

$basePath = dirname(__DIR__);
$modules = require $basePath.'/config/erp_modules.php';

$statusEnumCases = [
    'ProductionOrderStatus' => ['draft', 'planned', 'in_progress', 'completed', 'cancelled'],
    'QualityControlStatus' => ['pending', 'passed', 'failed', 'rework'],
    'DeliveryTripStatus' => ['planned', 'in_transit', 'delivered', 'cancelled'],
    'LeaveRequestStatus' => ['pending', 'approved', 'rejected', 'cancelled'],
    'PerformanceReviewStatus' => ['draft', 'submitted', 'completed'],
    'VehicleMaintenanceStatus' => ['scheduled', 'in_progress', 'completed', 'cancelled'],
    'LeadStatus' => ['new', 'contacted', 'qualified', 'converted', 'lost'],
    'CampaignStatus' => ['draft', 'active', 'paused', 'completed', 'cancelled'],
    'BankReconciliationStatus' => ['draft', 'in_progress', 'reconciled', 'cancelled'],
    'SubscriptionStatus' => ['active', 'paused', 'cancelled', 'expired'],
    'ContractStatus' => ['draft', 'active', 'expired', 'terminated'],
    'ProjectTaskStatus' => ['todo', 'in_progress', 'review', 'done', 'cancelled'],
    'TicketStatus' => ['open', 'in_progress', 'resolved', 'closed'],
    'StockTransferStatus' => ['draft', 'pending', 'in_transit', 'completed', 'cancelled'],
];

$fkModelMap = [
    'products' => 'Product',
    'contacts' => 'Contact',
    'warehouses' => 'Warehouse',
    'employees' => 'Employee',
    'branches' => 'Branch',
    'production_orders' => 'ProductionOrder',
    'shipping_methods' => 'ShippingMethod',
    'project_tasks' => 'ProjectTask',
    'contracts' => 'Contract',
];

$migrationOrder = [
    'branches', 'notification-templates', 'variants', 'price-lists',
    'shipping-methods', 'production-orders', 'bill-of-materials', 'quality-controls',
    'delivery-trips', 'shift-management', 'attendance', 'leave-requests',
    'performance-reviews', 'vehicle-maintenance', 'leads', 'customer-feedback',
    'campaigns', 'loyalty-programs', 'coupons', 'fixed-assets', 'budgeting',
    'bank-reconciliation', 'gift-cards', 'subscriptions', 'contracts',
    'project-tasks', 'time-logs', 'tickets', 'knowledge-base',
    'supplier-evaluations', 'floor-plans', 'stock-transfers',
];

$orderedModules = [];
foreach ($migrationOrder as $key) {
    if (isset($modules[$key])) {
        $orderedModules[$key] = $modules[$key];
    }
}

function ensureDir(string $path): void
{
    if (! is_dir($path)) {
        mkdir($path, 0755, true);
    }
}

function writeFile(string $path, string $content, bool $force = false): bool
{
    if (file_exists($path) && ! $force) {
        echo "SKIP (exists): {$path}\n";

        return false;
    }
    ensureDir(dirname($path));
    file_put_contents($path, $content);
    echo "WRITE: {$path}\n";

    return true;
}

function parseField(string $name, string $definition): array
{
    $parts = explode('|', $definition);
    $typePart = $parts[0];
    $meta = ['name' => $name, 'required' => false, 'nullable' => false, 'unique' => false, 'default' => null];

    foreach (array_slice($parts, 1) as $rule) {
        if ($rule === 'required') {
            $meta['required'] = true;
        } elseif ($rule === 'nullable') {
            $meta['nullable'] = true;
        } elseif ($rule === 'unique') {
            $meta['unique'] = true;
        } elseif (str_starts_with($rule, 'default:')) {
            $meta['default'] = substr($rule, 8);
        }
    }

    if (str_starts_with($typePart, 'foreignId:')) {
        $meta['type'] = 'foreignId';
        $meta['table'] = explode(':', $typePart)[1];
    } elseif (str_starts_with($typePart, 'decimal:')) {
        $meta['type'] = 'decimal';
        [$_, $precision] = explode(':', $typePart);
        [$p, $s] = explode(',', $precision);
        $meta['precision'] = (int) $p;
        $meta['scale'] = (int) $s;
    } else {
        $meta['type'] = $typePart;
    }

    return $meta;
}

function relationName(string $field, string $table): string
{
    if ($field === 'component_product_id') {
        return 'componentProduct';
    }
    if ($field === 'from_warehouse_id') {
        return 'fromWarehouse';
    }
    if ($field === 'to_warehouse_id') {
        return 'toWarehouse';
    }

    return Str::camel(str_replace('_id', '', $field));
}

function relationModel(string $field, string $table, array $fkModelMap): string
{
    if ($field === 'component_product_id') {
        return 'Product';
    }

    return $fkModelMap[$table] ?? Str::studly(Str::singular($table));
}

function migrationColumn(array $field): string
{
    $name = $field['name'];
    $nullable = $field['nullable'] || ! $field['required'];
    $suffix = $nullable ? '->nullable()' : '';

    return match ($field['type']) {
        'foreignId' => "\$table->foreignId('{$name}'){$suffix}->constrained()->nullOnDelete();",
        'string' => "\$table->string('{$name}')".($field['unique'] ? '->unique()' : '')."{$suffix};",
        'text' => "\$table->text('{$name}'){$suffix};",
        'boolean' => "\$table->boolean('{$name}')".($field['default'] !== null ? '->default('.($field['default'] === 'true' ? 'true' : 'false').')' : '').';',
        'integer' => "\$table->integer('{$name}')".($field['default'] !== null ? "->default({$field['default']})" : '')."{$suffix};",
        'decimal' => "\$table->decimal('{$name}', {$field['precision']}, {$field['scale']})".($field['default'] !== null ? "->default({$field['default']})" : '')."{$suffix};",
        'date' => "\$table->date('{$name}'){$suffix};",
        'time' => "\$table->time('{$name}'){$suffix};",
        'json' => "\$table->json('{$name}'){$suffix};",
        default => "\$table->string('{$name}'){$suffix};",
    };
}

function phpType(array $field, ?string $statusEnum = null): string
{
    if ($field['name'] === 'status' && $statusEnum) {
        return $statusEnum;
    }

    return match ($field['type']) {
        'foreignId' => 'int'.($field['nullable'] || ! $field['required'] ? '|null' : ''),
        'boolean' => 'bool',
        'integer' => 'int',
        'decimal' => 'string',
        'date', 'time' => 'Carbon',
        'json' => 'array|null',
        default => ($field['nullable'] || ! $field['required'] ? 'string|null' : 'string'),
    };
}

function castType(array $field, ?string $statusEnum = null): ?string
{
    if ($field['name'] === 'status' && $statusEnum) {
        return "{$statusEnum}::class";
    }

    return match ($field['type']) {
        'boolean' => 'boolean',
        'integer' => 'integer',
        'decimal' => "decimal:{$field['scale']}",
        'date' => 'date',
        'time' => 'datetime:H:i:s',
        'json' => 'array',
        default => null,
    };
}

function validationRules(array $field, string $table, ?string $statusEnum = null, ?int $ignoreId = null): string
{
    $name = $field['name'];
    $rules = [];

    if ($field['required'] && ! $field['nullable']) {
        $rules[] = "'required'";
    } else {
        $rules[] = "'nullable'";
    }

    if ($field['type'] === 'foreignId') {
        $rules[] = "'exists:{$field['table']},id'";
    } elseif ($field['type'] === 'boolean') {
        $rules[] = "'boolean'";
    } elseif ($field['type'] === 'integer') {
        $rules[] = "'integer'";
    } elseif ($field['type'] === 'decimal') {
        $rules[] = "'numeric'";
    } elseif ($field['type'] === 'date') {
        $rules[] = "'date'";
    } elseif ($field['type'] === 'time') {
        $rules[] = "'date_format:H:i'";
    } elseif ($field['type'] === 'json') {
        $rules[] = "'array'";
    } elseif ($name === 'status' && $statusEnum) {
        $rules[] = "Rule::enum({$statusEnum}::class)";
    } elseif ($field['type'] === 'text') {
        $rules[] = "'string'";
        $rules[] = "'max:5000'";
    } else {
        $rules[] = "'string'";
        $rules[] = "'max:255'";
    }

    if ($field['unique']) {
        $ignore = $ignoreId ? '->ignore($ignoreId)' : '';
        $rules[] = "Rule::unique('{$table}', '{$name}'){$ignore}";
    }

    return implode(', ', $rules);
}

function factoryValue(array $field, ?string $statusEnum = null): string
{
    $name = $field['name'];

    if ($name === 'status' && $statusEnum) {
        return "fake()->randomElement(array_column({$statusEnum}::cases(), 'value'))";
    }

    return match ($field['type']) {
        'foreignId' => match ($field['table']) {
            'products' => 'Product::factory()',
            'contacts' => 'Contact::factory()',
            'warehouses' => 'Warehouse::factory()',
            'employees' => 'Employee::factory()',
            'branches' => 'Branch::factory()',
            'production_orders' => 'ProductionOrder::factory()',
            'shipping_methods' => 'ShippingMethod::factory()',
            'project_tasks' => 'ProjectTask::factory()',
            'contracts' => 'Contract::factory()',
            default => '1',
        },
        'boolean' => 'fake()->boolean()',
        'integer' => $name === 'rating' || str_contains($name, 'score') ? 'fake()->numberBetween(1, 5)' : 'fake()->numberBetween(1, 100)',
        'decimal' => 'fake()->randomFloat(2, 0, 10000)',
        'date' => 'fake()->date()',
        'time' => "'09:00:00'",
        'json' => '[]',
        'text' => 'fake()->paragraph()',
        default => match ($name) {
            'email' => 'fake()->safeEmail()',
            'phone' => 'fake()->phoneNumber()',
            'code', 'sku', 'asset_code' => "fake()->unique()->bothify('??-####')",
            'reference_number' => "fake()->unique()->bothify('REF-######')",
            'slug' => 'fake()->unique()->slug()',
            'name', 'title', 'subject' => 'fake()->words(3, true)',
            'channel' => "'email'",
            'leave_type', 'maintenance_type', 'discount_type', 'billing_cycle', 'category', 'priority' => "'".($field['default'] ?? 'general')."'",
            'carrier', 'company', 'source', 'plan_name', 'bank_name', 'driver_name', 'vehicle_plate' => 'fake()->word()',
            'unit' => "'pcs'",
            default => 'fake()->word()',
        },
    };
}

function livewirePropertyType(array $field): string
{
    return match ($field['type']) {
        'foreignId' => '?int',
        'boolean' => 'bool',
        'integer' => 'int',
        'decimal' => 'string',
        'json' => 'string',
        default => 'string',
    };
}

function livewireDefault(array $field): string
{
    if ($field['type'] === 'boolean') {
        return $field['default'] === 'false' ? 'false' : 'true';
    }
    if ($field['type'] === 'foreignId') {
        return 'null';
    }
    if ($field['type'] === 'integer') {
        return $field['default'] ?? '0';
    }
    if ($field['type'] === 'decimal') {
        return "'".($field['default'] ?? '0')."'";
    }

    return "'".($field['default'] ?? '')."'";
}

function livewireFormField(array $field, ?string $statusEnum, array $fkModelMap): string
{
    $name = $field['name'];
    $label = Str::headline($name);

    if ($name === 'status' && $statusEnum) {
        $options = "@foreach ({$statusEnum}::options() as \$value => \$label)\n                <flux:select.option :value=\"\$value\">{{ \$label }}</flux:select.option>\n            @endforeach";

        return "<flux:select wire:model=\"status\" :label=\"__('$label')\">\n            {$options}\n        </flux:select>";
    }

    if ($field['type'] === 'foreignId') {
        $model = relationModel($name, $field['table'], $fkModelMap);
        $computed = Str::camel(Str::plural($model));
        $display = match ($model) {
            'Employee' => '$item->fullName()',
            'Contact' => '$item->name',
            'Product' => '$item->name',
            default => '$item->name',
        };

        return "<flux:select wire:model=\"{$name}\" :label=\"__('$label')\" :placeholder=\"__('Select')\">\n            <flux:select.option value=\"\">{{ __('None') }}</flux:select.option>\n            @foreach (\$this->{$computed} as \$item)\n                <flux:select.option :value=\"\$item->id\">{{ {$display} }}</flux:select.option>\n            @endforeach\n        </flux:select>";
    }

    if ($field['type'] === 'boolean') {
        return "<flux:switch wire:model=\"{$name}\" :label=\"__('$label')\" />";
    }

    if ($field['type'] === 'text' || $name === 'content' || $name === 'description' || $name === 'feedback' || $name === 'comments' || $name === 'notes' || $name === 'reason') {
        return "<flux:textarea wire:model=\"{$name}\" :label=\"__('$label')\" />";
    }

    if ($field['type'] === 'date') {
        $req = $field['required'] ? ' required' : '';

        return "<flux:input wire:model=\"{$name}\" type=\"date\" :label=\"__('$label')\"{$req} />";
    }

    if ($field['type'] === 'time') {
        return "<flux:input wire:model=\"{$name}\" type=\"time\" :label=\"__('$label')\" />";
    }

    if ($field['type'] === 'integer' || $field['type'] === 'decimal') {
        $step = $field['type'] === 'decimal' ? ' step="0.01"' : '';
        $req = $field['required'] ? ' required' : '';

        return "<flux:input wire:model=\"{$name}\" type=\"number\"{$step} :label=\"__('$label')\"{$req} />";
    }

    if ($field['type'] === 'json') {
        return "<flux:textarea wire:model=\"{$name}\" :label=\"__('$label')\" :placeholder=\"__('JSON')\" />";
    }

    $req = $field['required'] ? ' required' : '';

    return "<flux:input wire:model=\"{$name}\" :label=\"__('$label')\"{$req} />";
}

function indexColumns(array $fields, ?string $statusEnum): array
{
    $priority = ['reference_number', 'name', 'title', 'code', 'subject', 'employee_id', 'contact_id', 'product_id', 'status', 'created_at'];
    $cols = [];

    foreach ($priority as $p) {
        foreach ($fields as $f) {
            if ($f['name'] === $p) {
                $cols[] = $f;
            }
        }
    }

    if (count($cols) < 4) {
        foreach ($fields as $f) {
            if (count($cols) >= 5) {
                break;
            }
            if (! in_array($f, $cols, true) && ! in_array($f['name'], ['notes', 'description', 'content', 'feedback', 'comments', 'reason', 'layout_data'], true)) {
                $cols[] = $f;
            }
        }
    }

    return array_slice($cols, 0, 6);
}

function indexCellValue(array $field, string $var, array $fkModelMap): string
{
    $name = $field['name'];

    if ($field['type'] === 'foreignId') {
        $rel = relationName($name, $field['table']);

        return "{{ \${$var}->{$rel}?->name ?? \${$var}->{$rel}?->fullName() ?? '—' }}";
    }

    if ($name === 'status') {
        return "<flux:badge size=\"sm\" :color=\"\${$var}->status->color()\">{{ \${$var}->status->label() }}</flux:badge>";
    }

    if ($field['type'] === 'boolean') {
        return "{{ \${$var}->{$name} ? __('Yes') : __('No') }}";
    }

    if ($field['type'] === 'date') {
        return "{{ \${$var}->{$name}?->format('Y-m-d') ?? '—' }}";
    }

    if ($field['type'] === 'decimal') {
        return "{{ number_format((float) \${$var}->{$name}, 2) }}";
    }

    return "{{ \${$var}->{$name} ?? '—' }}";
}

function searchFields(array $fields): array
{
    $searchable = [];
    foreach ($fields as $f) {
        if (in_array($f['type'], ['string', 'text'], true) && ! in_array($f['name'], ['slug', 'layout_data'], true)) {
            $searchable[] = $f['name'];
        }
    }

    return array_slice($searchable, 0, 4);
}

// Generate status enums
foreach ($statusEnumCases as $enumName => $cases) {
    $caseLines = '';
    foreach ($cases as $case) {
        $const = Str::studly(str_replace('_', ' ', $case));
        $const = str_replace(' ', '', $const);
        if (is_numeric($case[0] ?? '')) {
            $const = '_'.$const;
        }
        $caseLines .= "    case {$const} = '{$case}';\n";
    }

    $labelCases = implode("\n            ", array_map(function ($case) {
        $const = Str::studly(str_replace('_', ' ', $case));
        $const = str_replace(' ', '', $const);
        if (is_numeric($case[0] ?? '')) {
            $const = '_'.$const;
        }
        $label = Str::headline($case);

        return "self::{$const} => __('{$label}'),";
    }, $cases));

    $colorCases = implode("\n            ", array_map(function ($case, $i) {
        $const = Str::studly(str_replace('_', ' ', $case));
        $const = str_replace(' ', '', $const);
        if (is_numeric($case[0] ?? '')) {
            $const = '_'.$const;
        }
        $colors = ['zinc', 'blue', 'amber', 'green', 'red', 'purple'];
        $color = $colors[$i % count($colors)];

        return "self::{$const} => '{$color}',";
    }, $cases, array_keys($cases)));

    $enumContent = <<<PHP
<?php

namespace App\Enums;

enum {$enumName}: string
{
{$caseLines}
    /**
     * @return array<string, string>
     */
    public static function options(): array
    {
        return collect(self::cases())
            ->mapWithKeys(fn (self \$status) => [\$status->value => \$status->label()])
            ->all();
    }

    public function label(): string
    {
        return match (\$this) {
            {$labelCases}
        };
    }

    public function color(): string
    {
        return match (\$this) {
            {$colorCases}
        };
    }
}

PHP;
    writeFile("{$basePath}/app/Enums/{$enumName}.php", $enumContent);
}

$routeFiles = [];
$migrationIndex = 1;

foreach ($orderedModules as $key => $config) {
    $model = $config['model'];
    $table = $config['table'];
    $route = $config['route'];
    $prefix = $config['prefix'];
    $permission = $config['permission'];
    $statusEnum = $config['status_enum'] ?? null;
    $routeParam = Str::camel($model);
    $varName = Str::camel($model);
    $pluralVar = Str::camel(Str::plural($model));
    $label = $config['sidebar']['label'] ?? Str::headline($route);
    $icon = $config['sidebar']['icon'] ?? 'document';

    $modelPath = "{$basePath}/app/Models/{$model}.php";
    if (file_exists($modelPath)) {
        echo "SKIP MODULE (model exists): {$model}\n";

        continue;
    }

    $parsedFields = [];
    foreach ($config['fields'] as $fname => $fdef) {
        $parsedFields[] = parseField($fname, $fdef);
    }

    // Migration
    $columns = '';
    foreach ($parsedFields as $pf) {
        $columns .= '            '.migrationColumn($pf)."\n";
    }
    $indexes = '';
    if ($statusEnum) {
        $indexes .= "\n            \$table->index('status');";
    }
    foreach ($parsedFields as $pf) {
        if ($pf['type'] === 'foreignId') {
            $indexes .= "\n            \$table->index('{$pf['name']}');";
        }
    }

    $migrationNum = str_pad((string) $migrationIndex, 6, '0', STR_PAD_LEFT);
    $migrationContent = <<<PHP
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('{$table}', function (Blueprint \$table) {
            \$table->id();
{$columns}            \$table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            \$table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();
            \$table->timestamps();
            \$table->softDeletes();{$indexes}
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('{$table}');
    }
};

PHP;
    writeFile("{$basePath}/database/migrations/2026_07_13_{$migrationNum}_create_{$table}_table.php", $migrationContent);
    $migrationIndex++;

    // Model
    $properties = '';
    $fillable = [];
    $casts = [];
    $relations = '';
    $uses = ['HasFactory', 'SoftDeletes', 'Auditable'];
    $imports = [
        'use App\Concerns\Auditable;',
        'use Database\Factories\\'.$model.'Factory;',
        'use Illuminate\Database\Eloquent\Attributes\Fillable;',
        'use Illuminate\Database\Eloquent\Factories\HasFactory;',
        'use Illuminate\Database\Eloquent\Model;',
        'use Illuminate\Database\Eloquent\Relations\BelongsTo;',
        'use Illuminate\Database\Eloquent\SoftDeletes;',
        'use Illuminate\Support\Carbon;',
    ];

    if ($statusEnum) {
        $imports[] = "use App\Enums\\{$statusEnum};";
    }

    foreach ($parsedFields as $pf) {
        $properties .= ' * @property '.phpType($pf, $statusEnum)." \${$pf['name']}\n";
        $fillable[] = $pf['name'];
        $cast = castType($pf, $statusEnum);
        if ($cast) {
            $casts[$pf['name']] = $cast;
        }
        if ($pf['type'] === 'foreignId') {
            $rel = relationName($pf['name'], $pf['table']);
            $relModel = relationModel($pf['name'], $pf['table'], $fkModelMap);
            $belongsToArgs = $pf['name'] !== $rel.'_id' ? ", '{$pf['name']}'" : '';
            $relations .= <<<PHP

    /**
     * @return BelongsTo<{$relModel}, \$this>
     */
    public function {$rel}(): BelongsTo
    {
        return \$this->belongsTo({$relModel}::class{$belongsToArgs});
    }

PHP;
        }
    }

    $fillable[] = 'created_by';
    $fillable[] = 'updated_by';
    $properties .= " * @property int|null \$created_by\n * @property int|null \$updated_by\n * @property Carbon|null \$created_at\n * @property Carbon|null \$updated_at\n * @property Carbon|null \$deleted_at\n";

    $fillableStr = implode(",\n    ", array_map(fn ($f) => "'{$f}'", $fillable));
    $castsStr = '';
    foreach ($casts as $k => $v) {
        $castsStr .= "            '{$k}' => {$v},\n";
    }

    $modelContent = <<<PHP
<?php

namespace App\Models;

{$imports[0]}
{$imports[1]}
{$imports[2]}
{$imports[3]}
{$imports[4]}
{$imports[5]}
{$imports[6]}
PHP;
    if ($statusEnum) {
        $modelContent .= "\nuse App\\Enums\\{$statusEnum};";
    }
    $modelContent .= <<<PHP


/**
 * @property int \$id
{$properties} */
#[Fillable([
    {$fillableStr},
])]
class {$model} extends Model
{
    /** @use HasFactory<{$model}Factory> */
    use HasFactory, SoftDeletes, Auditable;

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
{$castsStr}        ];
    }
{$relations}}
PHP;
    writeFile($modelPath, $modelContent);

    // Factory
    $factoryFields = '';
    $factoryUses = ["use App\Models\\{$model};", 'use Illuminate\Database\Eloquent\Factories\Factory;'];
    $factoryDeps = [];
    foreach ($parsedFields as $pf) {
        if ($pf['type'] === 'foreignId') {
            $rm = relationModel($pf['name'], $pf['table'], $fkModelMap);
            if (! in_array($rm, $factoryDeps, true) && $rm !== $model) {
                $factoryDeps[] = $rm;
            }
        }
        if ($statusEnum && ! in_array("use App\\Enums\\{$statusEnum};", $factoryUses, true)) {
            $factoryUses[] = "use App\\Enums\\{$statusEnum};";
        }
    }
    foreach ($factoryDeps as $dep) {
        $factoryUses[] = "use App\Models\\{$dep};";
    }

    foreach ($parsedFields as $pf) {
        $val = factoryValue($pf, $statusEnum);
        if ($pf['type'] === 'foreignId' && str_ends_with($val, '::factory()')) {
            $factoryFields .= "            '{$pf['name']}' => {$val},\n";
        } else {
            $factoryFields .= "            '{$pf['name']}' => {$val},\n";
        }
    }

    $factoryUseStr = implode("\n", array_unique($factoryUses));
    $factoryContent = <<<PHP
<?php

namespace Database\Factories;

{$factoryUseStr}

/**
 * @extends Factory<{$model}>
 */
class {$model}Factory extends Factory
{
    protected \$model = {$model}::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
{$factoryFields}        ];
    }
}

PHP;
    writeFile("{$basePath}/database/factories/{$model}Factory.php", $factoryContent);

    // Repository
    $repoContent = <<<PHP
<?php

namespace App\Repositories;

use App\Models\\{$model};

/**
 * @extends BaseRepository<{$model}>
 */
class {$model}Repository extends BaseRepository
{
    protected string \$model = {$model}::class;
}

PHP;
    writeFile("{$basePath}/app/Repositories/{$model}Repository.php", $repoContent);

    // Service
    $serviceContent = <<<PHP
<?php

namespace App\Services;

use App\Repositories\\{$model}Repository;

class {$model}Service extends BaseService
{
    public function __construct({$model}Repository \$repository)
    {
        parent::__construct(\$repository);
    }
}

PHP;
    writeFile("{$basePath}/app/Services/{$model}Service.php", $serviceContent);

    // Policy
    $policyContent = <<<PHP
<?php

namespace App\Policies;

use App\Models\\{$model};
use App\Models\User;

class {$model}Policy
{
    public function viewAny(User \$user): bool
    {
        return \$user->can('{$permission}.read');
    }

    public function view(User \$user, {$model} \${$routeParam}): bool
    {
        return \$user->can('{$permission}.read');
    }

    public function create(User \$user): bool
    {
        return \$user->can('{$permission}.create');
    }

    public function update(User \$user, {$model} \${$routeParam}): bool
    {
        return \$user->can('{$permission}.update');
    }

    public function delete(User \$user, {$model} \${$routeParam}): bool
    {
        return \$user->can('{$permission}.delete');
    }
}

PHP;
    writeFile("{$basePath}/app/Policies/{$model}Policy.php", $policyContent);

    // Form Requests
    $rulesStore = '';
    $rulesUpdate = '';
    foreach ($parsedFields as $pf) {
        $rulesStore .= "            '{$pf['name']}' => [".validationRules($pf, $table, $statusEnum)."],\n";
        $rulesUpdate .= "            '{$pf['name']}' => [".validationRules($pf, $table, $statusEnum, null)."],\n";
    }

    $requestImports = ['use Illuminate\Foundation\Http\FormRequest;', 'use Illuminate\Validation\Rule;'];
    if ($statusEnum) {
        $requestImports[] = "use App\\Enums\\{$statusEnum};";
    }

    $storeRequest = <<<PHP
<?php

namespace App\Http\Requests;

{$requestImports[0]}
{$requestImports[1]}
PHP;
    if ($statusEnum) {
        $storeRequest .= "\nuse App\\Enums\\{$statusEnum};";
    }
    $storeRequest .= <<<PHP


class Store{$model}Request extends FormRequest
{
    public function authorize(): bool
    {
        return \$this->user()?->can('{$permission}.create') ?? false;
    }

    /**
     * @return array<string, array<int, mixed>>
     */
    public function rules(): array
    {
        return [
{$rulesStore}        ];
    }
}

PHP;
    writeFile("{$basePath}/app/Http/Requests/Store{$model}Request.php", $storeRequest);

    $updateRequest = <<<PHP
<?php

namespace App\Http\Requests;

{$requestImports[0]}
{$requestImports[1]}
PHP;
    if ($statusEnum) {
        $updateRequest .= "\nuse App\\Enums\\{$statusEnum};";
    }
    $updateRequest .= <<<PHP


class Update{$model}Request extends FormRequest
{
    public function authorize(): bool
    {
        return \$this->user()?->can('{$permission}.update') ?? false;
    }

    /**
     * @return array<string, array<int, mixed>>
     */
    public function rules(): array
    {
        \$id = \$this->route('{$routeParam}')?->id;

        return [
PHP;
    foreach ($parsedFields as $pf) {
        $updateRequest .= "            '{$pf['name']}' => [".validationRules($pf, $table, $statusEnum)."],\n";
        if ($pf['unique']) {
            // fix unique ignore - regenerate with $id
        }
    }
    // Rebuild update rules properly
    $updateRules = '';
    foreach ($parsedFields as $pf) {
        $rules = validationRules($pf, $table, $statusEnum);
        if ($pf['unique']) {
            $rules = str_replace("Rule::unique('{$table}', '{$pf['name']}')", "Rule::unique('{$table}', '{$pf['name']}')->ignore(\$id)", $rules);
        }
        $updateRules .= "            '{$pf['name']}' => [{$rules}],\n";
    }
    $updateRequest = <<<PHP
<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
PHP;
    if ($statusEnum) {
        $updateRequest .= "\nuse App\\Enums\\{$statusEnum};";
    }
    $updateRequest .= <<<PHP


class Update{$model}Request extends FormRequest
{
    public function authorize(): bool
    {
        return \$this->user()?->can('{$permission}.update') ?? false;
    }

    /**
     * @return array<string, array<int, mixed>>
     */
    public function rules(): array
    {
        \$id = \$this->route('{$routeParam}')?->id;

        return [
{$updateRules}        ];
    }
}

PHP;
    writeFile("{$basePath}/app/Http/Requests/Update{$model}Request.php", $updateRequest);

    // Controller
    $controllerContent = <<<PHP
<?php

namespace App\Http\Controllers;

use App\Http\Requests\Store{$model}Request;
use App\Http\Requests\Update{$model}Request;
use App\Models\\{$model};
use App\Services\\{$model}Service;
use Illuminate\Http\RedirectResponse;

class {$model}Controller extends Controller
{
    public function __construct(
        protected {$model}Service \$service,
    ) {}

    public function store(Store{$model}Request \$request): RedirectResponse
    {
        \$this->service->store(\$request->validated());

        return redirect()
            ->route('{$route}.index')
            ->with('status', __('{$label} created successfully.'));
    }

    public function update(Update{$model}Request \$request, {$model} \${$routeParam}): RedirectResponse
    {
        \$this->service->update(\${$routeParam}, \$request->validated());

        return redirect()
            ->route('{$route}.index')
            ->with('status', __('{$label} updated successfully.'));
    }

    public function destroy({$model} \${$routeParam}): RedirectResponse
    {
        \$this->service->destroy(\${$routeParam});

        return redirect()
            ->route('{$route}.index')
            ->with('status', __('{$label} deleted successfully.'));
    }
}

PHP;
    writeFile("{$basePath}/app/Http/Controllers/{$model}Controller.php", $controllerContent);

    // Routes entry
    $routeFiles[$prefix][] = $config;

    // Livewire pages
    $domain = $prefix;
    $viewDir = "{$basePath}/resources/views/pages/{$domain}";
    ensureDir($viewDir);

    $searchable = searchFields($parsedFields);
    $searchQuery = '';
    foreach ($searchable as $sf) {
        $searchQuery .= "->orWhere('{$sf}', 'like', \"%{\$this->search}%\")\n                        ";
    }
    $searchQuery = rtrim($searchQuery);

    $withRelations = [];
    foreach ($parsedFields as $pf) {
        if ($pf['type'] === 'foreignId') {
            $withRelations[] = relationName($pf['name'], $pf['table']);
        }
    }
    $withStr = empty($withRelations) ? '' : "->with(['".implode("', '", array_slice($withRelations, 0, 3))."'])";

    $statusFilter = '';
    $statusImport = '';
    $statusSelect = '';
    if ($statusEnum) {
        $statusImport = "use App\\Enums\\{$statusEnum};\n";
        $statusFilter = "->when(\$this->status, fn (\$query) => \$query->where('status', \$this->status))";
        $statusSelect = <<<BLADE

        <flux:select wire:model.live="status" :placeholder="__('All statuses')">
            <flux:select.option value="">{{ __('All statuses') }}</flux:select.option>
            @foreach ({$statusEnum}::options() as \$value => \$label)
                <flux:select.option :value="\$value">{{ \$label }}</flux:select.option>
            @endforeach
        </flux:select>
BLADE;
    }

    $cols = indexColumns($parsedFields, $statusEnum);
    $tableColumns = '';
    $tableCells = '';
    foreach ($cols as $col) {
        $tableColumns .= "                <flux:table.column>{{ __('".Str::headline($col['name'])."') }}</flux:table.column>\n";
        $tableCells .= '                        <flux:table.cell>'.indexCellValue($col, $varName, $fkModelMap)."</flux:table.cell>\n";
    }
    $colCount = count($cols) + 1;

    $deleteMethod = 'delete'.Str::studly($model);
    $deleteVar = Str::camel($model).'ToDelete';

    $indexBlade = <<<BLADE
<?php

use App\Models\\{$model};
{$statusImport}use Flux\Flux;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Title;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithPagination;

new #[Title('{$label}')] class extends Component {
    use WithPagination;

    #[Url(as: 'q')]
    public string \$search = '';
BLADE;
    if ($statusEnum) {
        $indexBlade .= "\n\n    #[Url]\n    public string \$status = '';";
    }
    $indexBlade .= <<<BLADE


    public ?int \${$deleteVar} = null;

    public bool \$showDeleteModal = false;

    #[Computed]
    public function {$pluralVar}()
    {
        return {$model}::query()
            {$withStr}
            ->when(\$this->search, function (\$query) {
                \$query->where(function (\$query) {
                    \$query->where('{$searchable[0]}', 'like', "%{\$this->search}%")
                        {$searchQuery};
                });
            })
            {$statusFilter}
            ->latest()
            ->paginate(10);
    }

    public function updatedSearch(): void
    {
        \$this->resetPage();
    }
BLADE;
    if ($statusEnum) {
        $indexBlade .= "\n\n    public function updatedStatus(): void\n    {\n        \$this->resetPage();\n    }";
    }
    $indexBlade .= <<<BLADE


    public function confirmDelete(int \$id): void
    {
        \$this->{$deleteVar} = \$id;
        \$this->showDeleteModal = true;
    }

    public function {$deleteMethod}(): void
    {
        if (\$this->{$deleteVar} === null) {
            return;
        }

        {$model}::query()->findOrFail(\$this->{$deleteVar})->delete();

        \$this->{$deleteVar} = null;
        \$this->showDeleteModal = false;

        Flux::toast(variant: 'success', text: __('{$label} deleted successfully.'));
    }
}; ?>

<section class="w-full">
    <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
        <div>
            <flux:heading size="xl">{{ __('{$label}') }}</flux:heading>
            <flux:subheading>{{ __('Manage {$label}') }}</flux:subheading>
        </div>

        <flux:button :href="route('{$route}.create')" icon="plus" variant="primary" wire:navigate>
            {{ __('Add') }}
        </flux:button>
    </div>

    <div class="mt-6 grid gap-4 md:grid-cols-2">
        <flux:input wire:model.live.debounce.300ms="search" icon="magnifying-glass" :placeholder="__('Search...')" />
{$statusSelect}
    </div>

    <div class="mt-6">
        <flux:table :paginate="\$this->{$pluralVar}">
            <flux:table.columns>
{$tableColumns}                <flux:table.column>{{ __('Actions') }}</flux:table.column>
            </flux:table.columns>

            <flux:table.rows>
                @forelse (\$this->{$pluralVar} as \${$varName})
                    <flux:table.row wire:key="{$route}-{{ \${$varName}->id }}">
{$tableCells}                        <flux:table.cell>
                            <div class="flex items-center gap-2">
                                <flux:button size="sm" variant="ghost" icon="pencil-square" :href="route('{$route}.edit', \${$varName})" wire:navigate />
                                <flux:button size="sm" variant="ghost" icon="trash" wire:click="confirmDelete({{ \${$varName}->id }})" />
                            </div>
                        </flux:table.cell>
                    </flux:table.row>
                @empty
                    <flux:table.row>
                        <flux:table.cell colspan="{$colCount}">
                            <div class="py-8 text-center">
                                <flux:text>{{ __('No records found.') }}</flux:text>
                            </div>
                        </flux:table.cell>
                    </flux:table.row>
                @endforelse
            </flux:table.rows>
        </flux:table>
    </div>

    <flux:modal wire:model="showDeleteModal" class="max-w-md">
        <div class="space-y-6">
            <div>
                <flux:heading size="lg">{{ __('Delete') }}</flux:heading>
                <flux:text class="mt-2">{{ __('Are you sure? This action cannot be undone.') }}</flux:text>
            </div>
            <div class="flex justify-end gap-2">
                <flux:modal.close><flux:button variant="ghost">{{ __('Cancel') }}</flux:button></flux:modal.close>
                <flux:button variant="danger" wire:click="{$deleteMethod}">{{ __('Delete') }}</flux:button>
            </div>
        </div>
    </flux:modal>
</section>

BLADE;
    writeFile("{$viewDir}/⚡{$route}-index.blade.php", $indexBlade);

    // Create page
    $props = '';
    $mountDefaults = '';
    $computedFk = '';
    $computedUses = [];
    $formFields = '';
    $validationTrait = '';

    foreach ($parsedFields as $pf) {
        $props .= '    public '.livewirePropertyType($pf)." \${$pf['name']} = ".livewireDefault($pf).";\n";
        if ($pf['type'] === 'date' && ($pf['name'] === 'order_date' || str_ends_with($pf['name'], '_date') || $pf['name'] === 'log_date' || $pf['name'] === 'trip_date' || $pf['name'] === 'inspection_date' || $pf['name'] === 'review_date' || $pf['name'] === 'attendance_date' || $pf['name'] === 'maintenance_date' || $pf['name'] === 'evaluation_date' || $pf['name'] === 'feedback_date' || $pf['name'] === 'statement_date' || $pf['name'] === 'transfer_date')) {
            $mountDefaults .= "        \$this->{$pf['name']} = now()->format('Y-m-d');\n";
        }
        if ($pf['type'] === 'foreignId') {
            $rm = relationModel($pf['name'], $pf['table'], $fkModelMap);
            $computedName = Str::camel(Str::plural($rm));
            if (! in_array($computedName, $computedUses, true)) {
                $computedUses[] = $computedName;
                $computedFk .= <<<PHP

    #[Computed]
    public function {$computedName}()
    {
        return \\App\\Models\\{$rm}::query()->orderBy('name')->get();
    }

PHP;
            }
        }
        $formFields .= '        '.livewireFormField($pf, $statusEnum, $fkModelMap)."\n";
    }

    $createUses = "use App\Models\\{$model};\n";
    if ($statusEnum) {
        $createUses .= "use App\\Enums\\{$statusEnum};\n";
    }
    foreach ($computedUses as $cu) {
        $m = Str::studly(Str::singular($cu));
        $createUses .= "use App\Models\\{$m};\n";
    }

    $createBlade = <<<BLADE
<?php

{$createUses}use Flux\Flux;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Title;
use Livewire\Component;

new #[Title('Create {$label}')] class extends Component {
{$props}
    public function mount(): void
    {
{$mountDefaults}    }
{$computedFk}
    public function save(): void
    {
        \$validated = \$this->validate(app(\\App\\Http\\Requests\\Store{$model}Request::class)->rules());

        {$model}::query()->create(\$validated);

        Flux::toast(variant: 'success', text: __('{$label} created successfully.'));

        \$this->redirect(route('{$route}.index'), navigate: true);
    }
}; ?>

<section class="w-full">
    <div class="mb-6">
        <flux:heading size="xl">{{ __('Create {$label}') }}</flux:heading>
    </div>

    <form wire:submit="save" class="grid max-w-2xl gap-6">
{$formFields}
        <div class="flex gap-2">
            <flux:button type="submit" variant="primary">{{ __('Create') }}</flux:button>
            <flux:button :href="route('{$route}.index')" variant="ghost" wire:navigate>{{ __('Cancel') }}</flux:button>
        </div>
    </form>
</section>

BLADE;
    writeFile("{$viewDir}/⚡{$route}-create.blade.php", $createBlade);

    // Edit page
    $mountAssign = '';
    foreach ($parsedFields as $pf) {
        if ($pf['type'] === 'date') {
            $mountAssign .= "        \$this->{$pf['name']} = \${$routeParam}->{$pf['name']}?->format('Y-m-d') ?? '';\n";
        } elseif ($pf['type'] === 'boolean') {
            $mountAssign .= "        \$this->{$pf['name']} = \${$routeParam}->{$pf['name']};\n";
        } elseif ($pf['name'] === 'status' && $statusEnum) {
            $mountAssign .= "        \$this->status = \${$routeParam}->status->value;\n";
        } elseif ($pf['type'] === 'foreignId') {
            $mountAssign .= "        \$this->{$pf['name']} = \${$routeParam}->{$pf['name']};\n";
        } elseif ($pf['type'] === 'json') {
            $mountAssign .= "        \$this->{$pf['name']} = json_encode(\${$routeParam}->{$pf['name']} ?? []);\n";
        } elseif ($pf['type'] === 'decimal' || $pf['type'] === 'integer') {
            $mountAssign .= "        \$this->{$pf['name']} = (string) \${$routeParam}->{$pf['name']};\n";
        } else {
            $mountAssign .= "        \$this->{$pf['name']} = \${$routeParam}->{$pf['name']} ?? '';\n";
        }
    }

    $editBlade = <<<BLADE
<?php

{$createUses}use Flux\Flux;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Title;
use Livewire\Component;

new #[Title('Edit {$label}')] class extends Component {
    public {$model} \${$routeParam};

{$props}
    public function mount({$model} \${$routeParam}): void
    {
        \$this->{$routeParam} = \${$routeParam};
{$mountAssign}    }
{$computedFk}
    public function save(): void
    {
        \$validated = \$this->validate(app(\\App\\Http\\Requests\\Update{$model}Request::class)->rules());

        \$this->{$routeParam}->update(\$validated);

        Flux::toast(variant: 'success', text: __('{$label} updated successfully.'));

        \$this->redirect(route('{$route}.index'), navigate: true);
    }
}; ?>

<section class="w-full">
    <div class="mb-6">
        <flux:heading size="xl">{{ __('Edit {$label}') }}</flux:heading>
    </div>

    <form wire:submit="save" class="grid max-w-2xl gap-6">
{$formFields}
        <div class="flex gap-2">
            <flux:button type="submit" variant="primary">{{ __('Save changes') }}</flux:button>
            <flux:button :href="route('{$route}.index')" variant="ghost" wire:navigate>{{ __('Cancel') }}</flux:button>
        </div>
    </form>
</section>

BLADE;
    writeFile("{$viewDir}/⚡{$route}-edit.blade.php", $editBlade);
}

// Generate route files
foreach ($routeFiles as $prefix => $configs) {
    $routeContent = "<?php\n\nuse Illuminate\\Support\\Facades\\Route;\n\n";
    foreach ($configs as $config) {
        $model = $config['model'];
        $route = $config['route'];
        $routeParam = Str::camel($model);
        $routeContent .= "use App\\Http\\Controllers\\{$model}Controller;\n";
    }
    $routeContent .= "\nRoute::middleware(['auth', 'verified'])->prefix('{$prefix}')->group(function () {\n";
    foreach ($configs as $config) {
        $model = $config['model'];
        $route = $config['route'];
        $routeParam = Str::camel($model);
        $routeContent .= <<<PHP

    Route::middleware(['can:{$config['permission']}.read'])->name('{$route}.')->group(function () {
        Route::livewire('{$route}', 'pages::{$prefix}.{$route}-index')->name('index');
        Route::livewire('{$route}/create', 'pages::{$prefix}.{$route}-create')->middleware('can:{$config['permission']}.create')->name('create');
        Route::livewire('{$route}/{{$routeParam}}/edit', 'pages::{$prefix}.{$route}-edit')->middleware('can:{$config['permission']}.update')->name('edit');

        Route::post('{$route}', [{$model}Controller::class, 'store'])->middleware('can:{$config['permission']}.create')->name('store');
        Route::put('{$route}/{{$routeParam}}', [{$model}Controller::class, 'update'])->middleware('can:{$config['permission']}.update')->name('update');
        Route::delete('{$route}/{{$routeParam}}', [{$model}Controller::class, 'destroy'])->middleware('can:{$config['permission']}.delete')->name('destroy');
    });

PHP;
    }
    $routeContent .= "});\n";
    writeFile("{$basePath}/routes/{$prefix}.php", $routeContent);
}

echo "\nScaffolding complete.\n";
