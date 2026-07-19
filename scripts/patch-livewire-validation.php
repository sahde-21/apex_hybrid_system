<?php

declare(strict_types=1);

require __DIR__.'/../vendor/autoload.php';

$app = require_once __DIR__.'/../bootstrap/app.php';
$app->make(Kernel::class)->bootstrap();

use Illuminate\Contracts\Console\Kernel;
use Illuminate\Support\Str;

$modules = require __DIR__.'/../config/erp_modules.php';

foreach ($modules as $config) {
    $model = $config['model'];
    $trait = $model.'ValidationRules';
    $traitFile = __DIR__."/../app/Concerns/{$trait}.php";

    $storeFile = __DIR__."/../app/Http/Requests/Store{$model}Request.php";
    $updateFile = __DIR__."/../app/Http/Requests/Update{$model}Request.php";

    if (! file_exists($storeFile) || ! file_exists($updateFile)) {
        continue;
    }

    $storeRules = extractRulesBody(file_get_contents($storeFile));
    $updateRules = extractRulesBody(file_get_contents($updateFile));

    $routeParam = Str::camel($model);
    $updateRules = preg_replace('/\$id\b/', '$'.$routeParam.'Id', $updateRules);

    $methodStore = Str::camel($model).'Rules';
    $methodUpdate = Str::camel($model).'UpdateRules';

    $uses = "use Illuminate\Contracts\Validation\ValidationRule;\nuse Illuminate\Validation\Rule;";
    if (! empty($config['status_enum'])) {
        $uses .= "\nuse App\\Enums\\{$config['status_enum']};";
    }

    $traitContent = <<<PHP
<?php

namespace App\Concerns;

{$uses}

trait {$trait}
{
    /**
     * @return array<string, array<int, ValidationRule|array<mixed>|string>>
     */
    protected function {$methodStore}(): array
    {
        return [
{$storeRules}
        ];
    }

    /**
     * @return array<string, array<int, ValidationRule|array<mixed>|string>>
     */
    protected function {$methodUpdate}(?int \${$routeParam}Id = null): array
    {
        return [
{$updateRules}
        ];
    }
}

PHP;

    file_put_contents($traitFile, $traitContent);
    echo "Fixed {$traitFile}\n";
}

function extractRulesBody(string $content): string
{
    if (! preg_match('/public function rules\(\): array\s*\{[\s\S]*?return\s*\[([\s\S]*?)\];\s*\}/', $content, $match)) {
        return '';
    }

    $body = trim($match[1]);
    $body = preg_replace('/^\s*\$id = .*;\s*/m', '', $body);

    return $body;
}

echo "Validation concerns complete.\n";
