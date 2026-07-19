<?php

namespace App\Http\Controllers;

use App\Services\Bi\BiReportService;
use App\Services\Export\ExportService;
use App\Support\Bi\BiFilter;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Symfony\Component\HttpFoundation\StreamedResponse;

class BiExportController extends Controller
{
    public function __construct(
        protected BiReportService $reports,
        protected ExportService $exports,
    ) {}

    public function csv(Request $request, string $type): StreamedResponse
    {
        $report = $this->authorizeReport($request, $type);

        return $this->exports->exportCsv(
            'bi-'.$type.'.csv',
            $report['headers'],
            $report['rows'],
        );
    }

    public function excel(Request $request, string $type): StreamedResponse
    {
        $report = $this->authorizeReport($request, $type);

        return $this->exports->exportExcel(
            'bi-'.$type.'.xls',
            $report['headers'],
            $report['rows'],
        );
    }

    public function pdf(Request $request, string $type): Response
    {
        $report = $this->authorizeReport($request, $type);

        $pdf = Pdf::loadView('print.a4.bi-report', [
            'title' => $report['title'],
            'headers' => $report['headers'],
            'rows' => $report['rows'],
            'printedAt' => now(),
        ]);

        return $pdf->download('bi-'.$type.'.pdf');
    }

    public function print(Request $request, string $type)
    {
        $report = $this->authorizeReport($request, $type);

        return view('print.a4.bi-report', [
            'title' => $report['title'],
            'headers' => $report['headers'],
            'rows' => $report['rows'],
            'printedAt' => now(),
        ]);
    }

    /**
     * @return array{headers: list<string>, rows: list<list<mixed>>, title: string}
     */
    protected function authorizeReport(Request $request, string $type): array
    {
        abort_unless(array_key_exists($type, config('bi.reports')), 404);

        $filter = BiFilter::fromRequest($request);

        return $this->reports->report($request->user(), $type, $filter);
    }
}
