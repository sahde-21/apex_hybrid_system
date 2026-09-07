<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Services\Export\ExportService;
use App\Services\Intelligence\DomainAnalyticsService;
use App\Services\Intelligence\ExecutiveAnalyticsService;
use App\Support\Analytics\AnalyticsFilter;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Symfony\Component\HttpFoundation\StreamedResponse;

class IntelligenceExportController extends Controller
{
    /**
     * @var list<string>
     */
    private const DOMAINS = [
        'executive',
        'financial',
        'sales',
        'purchasing',
        'inventory',
        'customers',
        'suppliers',
        'operations',
    ];

    public function __construct(
        protected ExportService $exports,
        protected ExecutiveAnalyticsService $executive,
        protected DomainAnalyticsService $domains,
    ) {}

    public function csv(Request $request, string $domain): StreamedResponse
    {
        $user = $request->user('web');
        abort_unless($user instanceof User && $user->can('intelligence.export'), 403);

        [$headers, $rows, $title] = $this->buildReport($request, $domain);

        return $this->exports->exportCsv('intelligence-'.$domain.'.csv', $headers, $rows);
    }

    public function pdf(Request $request, string $domain): Response
    {
        $user = $request->user('web');
        abort_unless($user instanceof User && $user->can('intelligence.export'), 403);

        [$headers, $rows, $title] = $this->buildReport($request, $domain);

        $pdf = Pdf::loadView('print.a4.bi-report', [
            'title' => $title,
            'headers' => $headers,
            'rows' => $rows,
            'printedAt' => now(),
        ]);

        return $pdf->download('intelligence-'.$domain.'.pdf');
    }

    /**
     * @return array{0: list<string>, 1: list<list<mixed>>, 2: string}
     */
    protected function buildReport(Request $request, string $domain): array
    {
        abort_unless(in_array($domain, self::DOMAINS, true), 404);

        $filter = AnalyticsFilter::fromRequest($request);
        $user = $request->user('web');

        abort_unless($user instanceof User, 403);

        if ($domain === 'executive') {
            abort_unless($user->can(config('intelligence.permissions.executive')), 403);
            $data = $this->executive->dashboard($user, $filter);
            $headers = [__('Metric'), __('Value')];
            $rows = $this->kpiRows($data['kpis'] ?? []);

            return [$headers, $rows, __('scf.intelligence.executive_title')];
        }

        $data = $this->domains->forDomain($user, $domain, $filter);
        $headers = [__('Metric'), __('Value')];
        $rows = $this->kpiRows($data['kpis'] ?? []);

        return [$headers, $rows, __('scf.intelligence.'.$domain.'_title')];
    }

    /**
     * @param  array<mixed, mixed>  $kpis
     * @return list<list<mixed>>
     */
    private function kpiRows(array $kpis): array
    {
        $rows = [];

        foreach ($kpis as $key => $value) {
            $rows[] = [$key, $value];
        }

        return $rows;
    }
}
