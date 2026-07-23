<?php

namespace App\Http\Controllers\Api\V1;

use App\Models\IntelligenceAlert;
use App\Models\IntelligenceRecommendation;
use App\Services\Alerts\SmartAlertService;
use App\Services\Forecasting\ForecastingService;
use App\Services\Intelligence\DomainAnalyticsService;
use App\Services\Intelligence\ExecutiveAnalyticsService;
use App\Services\Recommendations\RecommendationEngine;
use App\Services\Scoring\BusinessHealthScoreService;
use App\Support\Analytics\AnalyticsFilter;
use App\Support\Api\ApiAbilities;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class IntelligenceController extends ApiController
{
    public function executive(Request $request, ExecutiveAnalyticsService $service): JsonResponse
    {
        $this->authorizeApiRead(ApiAbilities::INTELLIGENCE_READ);

        return $this->respond(
            $service->dashboard($this->actor($request), AnalyticsFilter::fromRequest($request)),
            __('scf.intelligence.api_executive'),
            $request,
        );
    }

    public function domain(Request $request, DomainAnalyticsService $service, string $domain): JsonResponse
    {
        $this->authorizeApiRead(ApiAbilities::INTELLIGENCE_READ);

        return $this->respond(
            $service->forDomain($this->actor($request), $domain, AnalyticsFilter::fromRequest($request)),
            __('scf.intelligence.api_domain', ['domain' => $domain]),
            $request,
        );
    }

    public function healthScore(Request $request, BusinessHealthScoreService $service): JsonResponse
    {
        $this->authorizeApiRead(ApiAbilities::INTELLIGENCE_READ);

        return $this->respond(
            $service->score($this->actor($request), AnalyticsFilter::fromRequest($request))->toArray(),
            __('scf.intelligence.api_health_score'),
            $request,
        );
    }

    public function forecasts(Request $request, ExecutiveAnalyticsService $executive): JsonResponse
    {
        $this->authorizeApiRead(ApiAbilities::INTELLIGENCE_READ);

        return $this->respond(
            $executive->forecasts($this->actor($request), AnalyticsFilter::fromRequest($request)),
            __('scf.intelligence.api_forecasts'),
            $request,
        );
    }

    public function alerts(Request $request, SmartAlertService $alerts): JsonResponse
    {
        $this->authorizeApiRead(ApiAbilities::INTELLIGENCE_READ);

        return $this->respond(
            $alerts->activeForUser($this->actor($request))->values(),
            __('scf.intelligence.api_alerts'),
            $request,
        );
    }

    public function recommendations(Request $request, RecommendationEngine $engine): JsonResponse
    {
        $this->authorizeApiRead(ApiAbilities::INTELLIGENCE_READ);

        return $this->respond(
            $engine->activeForUser($this->actor($request))->values(),
            __('scf.intelligence.api_recommendations'),
            $request,
        );
    }

    public function acknowledgeAlert(Request $request, IntelligenceAlert $alert, SmartAlertService $service): JsonResponse
    {
        $this->authorizeApiWrite(ApiAbilities::INTELLIGENCE_MANAGE);

        return $this->respond(
            $service->acknowledge($this->actor($request), $alert),
            __('scf.intelligence.alert_acknowledged'),
            $request,
        );
    }

    public function dismissAlert(Request $request, IntelligenceAlert $alert, SmartAlertService $service): JsonResponse
    {
        $this->authorizeApiWrite(ApiAbilities::INTELLIGENCE_MANAGE);

        return $this->respond(
            $service->dismiss($this->actor($request), $alert),
            __('scf.intelligence.alert_dismissed'),
            $request,
        );
    }

    public function acknowledgeRecommendation(Request $request, IntelligenceRecommendation $recommendation, RecommendationEngine $engine): JsonResponse
    {
        $this->authorizeApiWrite(ApiAbilities::INTELLIGENCE_MANAGE);

        return $this->respond(
            $engine->acknowledge($this->actor($request), $recommendation),
            __('scf.intelligence.recommendation_acknowledged'),
            $request,
        );
    }

    public function dismissRecommendation(Request $request, IntelligenceRecommendation $recommendation, RecommendationEngine $engine): JsonResponse
    {
        $this->authorizeApiWrite(ApiAbilities::INTELLIGENCE_MANAGE);

        return $this->respond(
            $engine->dismiss($this->actor($request), $recommendation),
            __('scf.intelligence.recommendation_dismissed'),
            $request,
        );
    }
}
