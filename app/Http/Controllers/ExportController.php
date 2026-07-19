<?php

namespace App\Http\Controllers;

use App\Models\Contact;
use App\Models\Invoice;
use App\Models\Product;
use App\Services\Export\ExportService;
use App\Services\Print\PrintService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Response;
use Symfony\Component\HttpFoundation\StreamedResponse;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

class ExportController extends Controller
{
    /**
     * @var array<string, string>
     */
    protected array $permissionMap = [
        'products' => 'products.export',
        'contacts' => 'contacts.export',
        'invoices' => 'invoices.export',
    ];

    public function __invoke(string $type, ExportService $exportService): StreamedResponse
    {
        $this->authorizeExport($type);

        return match ($type) {
            'products' => $this->products($exportService),
            'contacts' => $this->contacts($exportService),
            'invoices' => $this->invoices($exportService),
            default => throw new NotFoundHttpException(__('scf.unknown_print_type')),
        };
    }

    public function excel(string $type, ExportService $exportService): StreamedResponse
    {
        $this->authorizeExport($type);

        return match ($type) {
            'products' => $this->products($exportService, excel: true),
            'contacts' => $this->contacts($exportService, excel: true),
            'invoices' => $this->invoices($exportService, excel: true),
            default => throw new NotFoundHttpException(__('scf.unknown_print_type')),
        };
    }

    public function pdf(string $type, int $id, ExportService $exportService, PrintService $printService): Response|RedirectResponse
    {
        $normalized = $type === 'invoice' ? 'invoices' : $type;
        $this->authorizeExport($normalized);

        if ($normalized !== 'invoices') {
            throw new NotFoundHttpException(__('scf.unknown_print_type'));
        }

        abort_unless(request()->user()?->can('invoices.print') ?? false, 403);

        $invoice = Invoice::query()->with('contact')->findOrFail($id);

        return $exportService->exportPdf(
            'print.a4.invoice',
            [
                'record' => $invoice,
                'layout' => PrintService::LAYOUT_A4,
                'printedAt' => now(),
            ],
            'invoice-'.$invoice->reference_number.'.pdf',
            route('print.document', ['type' => 'invoice', 'id' => $invoice->id, 'layout' => 'a4']),
        );
    }

    protected function authorizeExport(string $type): void
    {
        $permission = $this->permissionMap[$type] ?? null;
        $user = request()->user();

        abort_unless($user !== null && $permission !== null && $user->can($permission), 403);
    }

    protected function products(ExportService $exportService, bool $excel = false): StreamedResponse
    {
        $rows = Product::query()
            ->orderBy('name')
            ->get(['id', 'name', 'sku', 'purchase_price', 'sale_price', 'stock_quantity', 'minimum_stock_level'])
            ->map(fn (Product $product): array => [
                $product->id,
                $product->name,
                $product->sku,
                $product->purchase_price,
                $product->sale_price,
                $product->stock_quantity,
                $product->minimum_stock_level,
            ]);

        $headers = [
            'ID',
            __('scf.name'),
            __('scf.sku'),
            __('scf.purchase_price'),
            __('scf.sale_price'),
            __('scf.quantity'),
            __('scf.min_stock'),
        ];

        return $excel
            ? $exportService->exportExcel('products.xls', $headers, $rows)
            : $exportService->exportCsv('products.csv', $headers, $rows);
    }

    protected function contacts(ExportService $exportService, bool $excel = false): StreamedResponse
    {
        $rows = Contact::query()
            ->orderBy('name')
            ->get(['id', 'name', 'type', 'email', 'phone', 'company_name'])
            ->map(fn (Contact $contact): array => [
                $contact->id,
                $contact->name,
                $contact->type?->value ?? $contact->type,
                $contact->email,
                $contact->phone,
                $contact->company_name,
            ]);

        $headers = [
            'ID',
            __('scf.name'),
            __('scf.type'),
            __('scf.email'),
            __('scf.phone'),
            __('scf.company'),
        ];

        return $excel
            ? $exportService->exportExcel('contacts.xls', $headers, $rows)
            : $exportService->exportCsv('contacts.csv', $headers, $rows);
    }

    protected function invoices(ExportService $exportService, bool $excel = false): StreamedResponse
    {
        $rows = Invoice::query()
            ->with('contact:id,name')
            ->orderByDesc('invoice_date')
            ->get()
            ->map(fn (Invoice $invoice): array => [
                $invoice->id,
                $invoice->reference_number,
                $invoice->contact?->name,
                $invoice->invoice_date?->format('Y-m-d'),
                $invoice->due_date?->format('Y-m-d'),
                $invoice->status?->value ?? $invoice->status,
                $invoice->tax_amount,
                $invoice->total_amount,
            ]);

        $headers = [
            'ID',
            __('scf.reference'),
            __('scf.customer'),
            __('scf.date'),
            __('scf.due'),
            __('scf.status'),
            __('scf.tax'),
            __('scf.total'),
        ];

        return $excel
            ? $exportService->exportExcel('invoices.xls', $headers, $rows)
            : $exportService->exportCsv('invoices.csv', $headers, $rows);
    }
}
