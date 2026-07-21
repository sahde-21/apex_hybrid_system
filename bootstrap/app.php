<?php

use App\Exceptions\Api\BusinessConflictException;
use App\Exceptions\Api\IdempotencyConflictException;
use App\Http\Middleware\EnsureApiUserIsActive;
use App\Http\Middleware\EnsureUserIsActive;
use App\Http\Middleware\ForceJsonResponse;
use App\Http\Middleware\SecurityHeaders;
use App\Http\Middleware\SetLocale;
use App\Http\Responses\ApiResponse;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Auth\AuthenticationException;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpKernel\Exception\HttpExceptionInterface;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\HttpKernel\Exception\TooManyRequestsHttpException;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withSchedule(function (\Illuminate\Console\Scheduling\Schedule $schedule): void {
        $schedule->job(new \App\Jobs\MaintainDocumentStatusesJob)
            ->hourly()
            ->withoutOverlapping()
            ->name('maintain-document-statuses')
            ->when(fn () => config('performance.scheduler.overdue_documents', true)
                || config('performance.scheduler.expire_documents', true));

        $schedule->job(new \App\Jobs\PruneExpiredIdempotencyKeysJob)
            ->daily()
            ->withoutOverlapping()
            ->name('prune-idempotency-keys')
            ->when(fn () => config('performance.scheduler.prune_idempotency', true));

        $schedule->command('db:backup --prune')
            ->dailyAt('02:00')
            ->withoutOverlapping()
            ->name('database-backup')
            ->when(fn () => config('performance.scheduler.prune_backups', true));

        $schedule->command('scf:warm-cache')
            ->hourly()
            ->withoutOverlapping()
            ->name('warm-performance-cache')
            ->when(fn () => config('performance.scheduler.warm_cache', true));

        $schedule->job(new \App\Jobs\EvaluateSmartAlertsJob)
            ->hourly()
            ->withoutOverlapping()
            ->name('evaluate-smart-alerts')
            ->when(fn () => config('intelligence.enabled', true));

        $schedule->job(new \App\Jobs\RefreshRecommendationsJob)
            ->daily()
            ->withoutOverlapping()
            ->name('refresh-recommendations')
            ->when(fn () => config('intelligence.enabled', true));

        $schedule->job(new \App\Jobs\GenerateDailyExecutiveSnapshotJob)
            ->dailyAt('03:30')
            ->withoutOverlapping()
            ->name('executive-intelligence-snapshot')
            ->when(fn () => config('intelligence.enabled', true));

        $schedule->job(new \App\Jobs\PruneExpiredIntelligenceSnapshotsJob)
            ->weekly()
            ->withoutOverlapping()
            ->name('prune-intelligence-snapshots')
            ->when(fn () => config('intelligence.enabled', true));
    })
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->web(append: [
            SetLocale::class,
            EnsureUserIsActive::class,
            SecurityHeaders::class,
            \App\Http\Middleware\MeasureRequestPerformance::class,
        ]);

        $middleware->api(prepend: [
            ForceJsonResponse::class,
            \App\Http\Middleware\AssignRequestId::class,
        ]);

        $middleware->api(append: [
            SecurityHeaders::class,
            \App\Http\Middleware\MeasureRequestPerformance::class,
        ]);

        $middleware->statefulApi();
        $middleware->throttleApi('api');

        $middleware->alias([
            'api.active' => EnsureApiUserIsActive::class,
            'api.ability' => \App\Http\Middleware\EnsureApiTokenAbility::class,
            'api.idempotent' => \App\Http\Middleware\HandleIdempotency::class,
        ]);

        $middleware->validateCsrfTokens(except: [
            // Intentionally empty — all cookie/session mutations require CSRF.
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        $exceptions->shouldRenderJsonWhen(
            fn (Request $request) => $request->is('api/*'),
        );

        $exceptions->report(function (AuthorizationException $e): void {
            Log::warning('authorization.denied', [
                'message' => $e->getMessage(),
                'user_id' => auth()->id(),
                'url' => request()->fullUrl(),
                'ip' => request()->ip(),
            ]);
        });

        $exceptions->render(function (AuthenticationException $e, Request $request) {
            if ($request->is('api/*')) {
                return ApiResponse::error(__('Unauthenticated.'), 401);
            }

            if ($request->is('portal') || $request->is('portal/*')) {
                return redirect()->guest(route('portal.login'));
            }

            if ($request->is('supplier') || $request->is('supplier/*')) {
                return redirect()->guest(route('supplier.login'));
            }
        });

        $exceptions->render(function (AuthorizationException $e, Request $request) {
            if ($request->is('api/*')) {
                return ApiResponse::error(__('This action is unauthorized.'), 403);
            }
        });

        $exceptions->render(function (ValidationException $e, Request $request) {
            if ($request->is('api/*')) {
                $businessKeys = [
                    'quotation', 'invoice', 'payment', 'bill', 'status', 'purchase_request',
                    'rfq', 'purchase_order', 'sale_order', 'contact_id', 'amount',
                ];

                $status = collect(array_keys($e->errors()))
                    ->intersect($businessKeys)
                    ->isNotEmpty() ? 409 : $e->status;

                return ApiResponse::error(
                    message: $status === 409
                        ? __('scf.api.business_conflict')
                        : __('Validation failed.'),
                    status: $status,
                    errors: $e->errors(),
                );
            }
        });

        $exceptions->render(function (IdempotencyConflictException $e, Request $request) {
            if ($request->is('api/*')) {
                return ApiResponse::error($e->getMessage(), 409);
            }
        });

        $exceptions->render(function (BusinessConflictException $e, Request $request) {
            if ($request->is('api/*')) {
                return ApiResponse::error($e->getMessage(), 409, $e->errors());
            }
        });

        $exceptions->render(function (ModelNotFoundException $e, Request $request) {
            if ($request->is('api/*')) {
                return ApiResponse::error(__('Resource not found.'), 404);
            }
        });

        $exceptions->render(function (NotFoundHttpException $e, Request $request) {
            if ($request->is('api/*')) {
                return ApiResponse::error(__('Endpoint not found.'), 404);
            }
        });

        $exceptions->render(function (TooManyRequestsHttpException $e, Request $request) {
            if ($request->is('api/*')) {
                return ApiResponse::error(
                    message: __('Too many requests. Please try again later.'),
                    status: 429,
                    meta: [
                        'retry_after' => $e->getHeaders()['Retry-After'] ?? null,
                    ],
                );
            }
        });

        $exceptions->render(function (HttpExceptionInterface $e, Request $request) {
            if ($request->is('api/*')) {
                $message = app()->hasDebugModeEnabled() && $e->getMessage() !== ''
                    ? $e->getMessage()
                    : __('Request failed.');

                return ApiResponse::error($message, $e->getStatusCode());
            }
        });

        $exceptions->render(function (\Throwable $e, Request $request) {
            if (! $request->is('api/*')) {
                return null;
            }

            report($e);

            $message = app()->hasDebugModeEnabled()
                ? $e->getMessage()
                : __('Server Error');

            return ApiResponse::error($message, 500);
        });
    })->create();
