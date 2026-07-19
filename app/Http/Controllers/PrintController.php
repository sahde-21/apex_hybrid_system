<?php

namespace App\Http\Controllers;

use App\Models\Bill;
use App\Models\Expense;
use App\Models\Invoice;
use App\Models\Payment;
use App\Models\PosSale;
use App\Models\PurchaseOrder;
use App\Models\Quotation;
use App\Models\SaleOrder;
use App\Services\Print\PrintService;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

class PrintController extends Controller
{
    /**
     * @var array<string, class-string<Model>>
     */
    protected array $typeMap = [
        'invoice' => Invoice::class,
        'payment' => Payment::class,
        'sale-order' => SaleOrder::class,
        'purchase-order' => PurchaseOrder::class,
        'bill' => Bill::class,
        'quotation' => Quotation::class,
        'expense' => Expense::class,
        'pos-sale' => PosSale::class,
    ];

    /**
     * @var array<string, string>
     */
    protected array $permissionMap = [
        'invoice' => 'invoices.print',
        'payment' => 'payments.print',
        'sale-order' => 'sale-orders.print',
        'purchase-order' => 'purchase-orders.print',
        'bill' => 'bills.print',
        'quotation' => 'quotations.print',
        'expense' => 'expenses.print',
        'pos-sale' => 'pos.print',
    ];

    /**
     * @var array<string, list<string>>
     */
    protected array $eagerLoad = [
        'invoice' => ['contact', 'saleOrder'],
        'payment' => ['contact'],
        'sale-order' => ['contact', 'warehouse'],
        'purchase-order' => ['contact', 'warehouse'],
        'bill' => ['contact'],
        'quotation' => ['contact'],
        'expense' => ['contact'],
        'pos-sale' => ['contact', 'items', 'payments', 'user', 'register', 'invoice'],
    ];

    public function print(Request $request, string $type, int $id, PrintService $printService): View
    {
        $validated = $request->validate([
            'layout' => ['sometimes', 'string', 'in:a4,thermal_80mm'],
        ]);

        if (! isset($this->typeMap[$type], $this->permissionMap[$type])) {
            throw new NotFoundHttpException(__('scf.unknown_print_type'));
        }

        $this->authorizePermission($this->permissionMap[$type]);

        $modelClass = $this->typeMap[$type];
        $query = $modelClass::query();

        if (! empty($this->eagerLoad[$type])) {
            $query->with($this->eagerLoad[$type]);
        }

        /** @var Model $record */
        $record = $query->findOrFail($id);

        $layout = $validated['layout'] ?? PrintService::LAYOUT_A4;

        return $printService->render($modelClass, $record, $layout);
    }

    protected function authorizePermission(string $permission): void
    {
        $user = request()->user();

        abort_unless($user !== null && $user->can($permission), 403);
    }
}
