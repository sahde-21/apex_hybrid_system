<?php

namespace App\Services\Export;

use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Response;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ExportService
{
    /**
     * Stream a CSV download.
     *
     * @param  list<string>  $headers
     * @param  iterable<int, list<mixed>|array<string, mixed>>  $rows
     */
    public function exportCsv(string $filename, array $headers, iterable $rows): StreamedResponse
    {
        if (! str_ends_with(strtolower($filename), '.csv')) {
            $filename .= '.csv';
        }

        return response()->streamDownload(function () use ($headers, $rows): void {
            $handle = fopen('php://output', 'w');

            if ($handle === false) {
                return;
            }

            fprintf($handle, chr(0xEF).chr(0xBB).chr(0xBF));
            fputcsv($handle, $headers);

            foreach ($rows as $row) {
                fputcsv($handle, array_values((array) $row));
            }

            fclose($handle);
        }, $filename, [
            'Content-Type' => 'text/csv; charset=UTF-8',
        ]);
    }

    /**
     * Stream an Excel-compatible spreadsheet (SpreadsheetML / XML).
     *
     * @param  list<string>  $headers
     * @param  iterable<int, list<mixed>|array<string, mixed>>  $rows
     */
    public function exportExcel(string $filename, array $headers, iterable $rows): StreamedResponse
    {
        if (! str_ends_with(strtolower($filename), '.xls')) {
            $filename = preg_replace('/\.(csv|xlsx)$/i', '', $filename).'.xls';
        }

        return response()->streamDownload(function () use ($headers, $rows): void {
            echo '<?xml version="1.0" encoding="UTF-8"?>'."\n";
            echo '<?mso-application progid="Excel.Sheet"?>'."\n";
            echo '<Workbook xmlns="urn:schemas-microsoft-com:office:spreadsheet" xmlns:ss="urn:schemas-microsoft-com:office:spreadsheet">';
            echo '<Worksheet ss:Name="Export"><Table>';

            echo '<Row>';
            foreach ($headers as $header) {
                echo '<Cell><Data ss:Type="String">'.e((string) $header).'</Data></Cell>';
            }
            echo '</Row>';

            foreach ($rows as $row) {
                echo '<Row>';
                foreach (array_values((array) $row) as $value) {
                    $isNumeric = is_numeric($value) && ! str_starts_with((string) $value, '0');
                    $type = $isNumeric ? 'Number' : 'String';
                    echo '<Cell><Data ss:Type="'.$type.'">'.e((string) $value).'</Data></Cell>';
                }
                echo '</Row>';
            }

            echo '</Table></Worksheet></Workbook>';
        }, $filename, [
            'Content-Type' => 'application/vnd.ms-excel; charset=UTF-8',
        ]);
    }

    /**
     * Generate a PDF when DomPDF is installed; otherwise redirect to an HTML print view.
     *
     * @param  array<string, mixed>  $data
     */
    public function exportPdf(string $view, array $data, string $filename, ?string $printUrl = null): Response|RedirectResponse
    {
        if (! str_ends_with(strtolower($filename), '.pdf')) {
            $filename .= '.pdf';
        }

        if (class_exists(\Barryvdh\DomPDF\Facade\Pdf::class)) {
            $pdf = \Barryvdh\DomPDF\Facade\Pdf::loadView($view, $data);

            return $pdf->download($filename);
        }

        if ($printUrl !== null) {
            return redirect()->to($printUrl);
        }

        return response()
            ->view($view, $data)
            ->header('Content-Type', 'text/html; charset=UTF-8');
    }
}
