<?php

namespace App\Http\Controllers\Portal;

use App\Http\Controllers\Controller;
use App\Models\Invoice;
use App\Models\Payment;
use App\Models\PortalCustomer;
use App\Models\Quotation;
use App\Models\SaleOrder;
use App\Services\Print\PrintService;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Response;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;

class PortalDocumentController extends Controller
{
    /**
     * @var array<string, class-string<Model>>
     */
    protected array $typeMap = [
        'invoice' => Invoice::class,
        'quotation' => Quotation::class,
        'sale-order' => SaleOrder::class,
        'payment' => Payment::class,
    ];

    /**
     * @var array<string, list<string>>
     */
    protected array $eagerLoad = [
        'invoice' => ['contact', 'saleOrder', 'payments'],
        'quotation' => ['contact'],
        'sale-order' => ['contact', 'warehouse'],
        'payment' => ['contact', 'invoice'],
    ];

    public function print(string $type, int $id, PrintService $printService): View
    {
        abort_unless(isset($this->typeMap[$type]), 404);

        $modelClass = $this->typeMap[$type];
        $model = $this->ownedDocument($type, $id);

        return $printService->render($modelClass, $model, PrintService::LAYOUT_A4);
    }

    public function pdf(string $type, int $id): Response|StreamedResponse
    {
        $model = $this->ownedDocument($type, $id);

        if ($type !== 'invoice') {
            abort(404);
        }

        $pdf = Pdf::loadView('print.a4.invoice', [
            'record' => $model,
            'layout' => 'a4',
            'printedAt' => now(),
        ]);

        return $pdf->download(($model->reference_number ?? 'document').'.pdf');
    }

    protected function ownedDocument(string $type, int $id): mixed
    {
        abort_unless(isset($this->typeMap[$type]), 404);

        /** @var PortalCustomer $customer */
        $customer = auth('portal')->user();

        $query = $this->typeMap[$type]::query()->where('contact_id', $customer->contact_id);

        if (! empty($this->eagerLoad[$type])) {
            $query->with($this->eagerLoad[$type]);
        }

        return $query->findOrFail($id);
    }
}
