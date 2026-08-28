<?php

namespace App\Http\Controllers\Supplier;

use App\Enums\PaymentType;
use App\Http\Controllers\Controller;
use App\Models\Bill;
use App\Models\Contract;
use App\Models\Payment;
use App\Models\PortalSupplier;
use App\Models\PurchaseOrder;
use App\Services\Print\PrintService;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Response;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;

class SupplierDocumentController extends Controller
{
    /**
     * @var array<string, class-string<Model>>
     */
    protected array $typeMap = [
        'purchase-order' => PurchaseOrder::class,
        'bill' => Bill::class,
        'payment' => Payment::class,
        'contract' => Contract::class,
    ];

    /**
     * @var array<string, list<string>>
     */
    protected array $eagerLoad = [
        'purchase-order' => ['contact', 'warehouse'],
        'bill' => ['contact'],
        'payment' => ['contact'],
        'contract' => ['contact'],
    ];

    public function print(string $type, int $id, PrintService $printService): View
    {
        abort_unless(isset($this->typeMap[$type]), 404);

        $modelClass = $this->typeMap[$type];
        $model = $this->ownedDocument($type, $id);

        if ($type === 'contract') {
            return view('print.a4.contract', [
                'record' => $model,
                'layout' => 'a4',
                'printedAt' => now(),
            ]);
        }

        return $printService->render($modelClass, $model, PrintService::LAYOUT_A4);
    }

    public function pdf(string $type, int $id): Response|StreamedResponse
    {
        $model = $this->ownedDocument($type, $id);

        $view = match ($type) {
            'purchase-order' => 'print.a4.purchase-order',
            'bill' => 'print.a4.bill',
            'payment' => 'print.a4.payment',
            'contract' => 'print.a4.contract',
            default => abort(404),
        };

        $pdf = Pdf::loadView($view, [
            'record' => $model,
            'layout' => 'a4',
            'printedAt' => now(),
        ]);

        return $pdf->download(($model->reference_number ?? 'document').'.pdf');
    }

    protected function ownedDocument(string $type, int $id): mixed
    {
        abort_unless(isset($this->typeMap[$type]), 404);

        /** @var PortalSupplier $supplier */
        $supplier = auth('supplier')->user();

        $query = $this->typeMap[$type]::query()->where('contact_id', $supplier->contact_id);

        if ($type === 'payment') {
            $query->where('type', PaymentType::Outgoing);
        }

        if (! empty($this->eagerLoad[$type])) {
            $query->with($this->eagerLoad[$type]);
        }

        return $query->findOrFail($id);
    }
}
