<?php

namespace App\Providers;

use App\Listeners\BackupBeforeDestructiveDatabaseCommand;
use App\Listeners\RecordUserAuthenticationEvents;
use App\Models\Bill;
use App\Models\DatabaseNotification;
use App\Models\Expense;
use App\Models\Invoice;
use App\Models\Payment;
use App\Models\Payroll;
use App\Observers\AccountingDocumentObserver;
use App\Observers\DomainNotificationObserver;
use App\Policies\DatabaseNotificationPolicy;
use App\Support\Logging\RequestLogContext;
use App\Support\PermissionCache;
use Carbon\CarbonImmutable;
use Illuminate\Auth\Events\Failed;
use Illuminate\Auth\Events\Login;
use Illuminate\Auth\Events\Logout;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Console\Events\CommandStarting;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Date;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\ServiceProvider;
use Illuminate\Validation\Rules\Password;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        $this->configureDefaults();
        $this->configureDatabaseSafety();
        $this->configureRateLimiting();
        $this->configureNotifications();
        $this->configureAccounting();
        $this->configurePerformanceInstrumentation();

        // Repair empty Spatie permission cache that still has DB records.
        PermissionCache::healIfStale();

        // Privileged roles bypass all ability / policy / Spatie permission checks.
        Gate::before(function ($user, $ability) {
            if (! is_object($user) || ! method_exists($user, 'hasRole')) {
                return null;
            }

            $privileged = config('security.privileged_roles', ['super-admin', 'owner']);

            foreach ($privileged as $role) {
                if (is_string($role) && $user->hasRole($role)) {
                    return true;
                }
            }

            return null;
        });

        Gate::policy(DatabaseNotification::class, DatabaseNotificationPolicy::class);

        Event::listen(Login::class, [RecordUserAuthenticationEvents::class, 'handleLogin']);
        Event::listen(Logout::class, [RecordUserAuthenticationEvents::class, 'handleLogout']);
        Event::listen(Failed::class, [RecordUserAuthenticationEvents::class, 'handleFailed']);
    }

    protected function configureNotifications(): void
    {
        if (! config('notifications.domain_enabled', true)) {
            return;
        }

        $observer = DomainNotificationObserver::class;

        foreach (array_keys(config('notifications.domain', [])) as $modelClass) {
            if (is_string($modelClass) && class_exists($modelClass) && is_subclass_of($modelClass, Model::class)) {
                $modelClass::observe($observer);
            }
        }
    }

    /**
     * Configure default behaviors for production-ready applications.
     */
    protected function configureDefaults(): void
    {
        Date::use(CarbonImmutable::class);

        Password::defaults(fn (): ?Password => app()->isProduction()
            ? Password::min(12)
                ->mixedCase()
                ->letters()
                ->numbers()
                ->symbols()
                ->uncompromised()
            : null,
        );
    }

    /**
     * Block destructive Artisan DB commands outside testing unless explicitly allowed.
     */
    protected function configureDatabaseSafety(): void
    {
        $allowDestructive = filter_var(
            (string) env('ALLOW_DESTRUCTIVE_DB', false),
            FILTER_VALIDATE_BOOLEAN
        );

        // Prohibits: db:wipe, migrate:fresh, migrate:refresh, migrate:reset, migrate:rollback
        DB::prohibitDestructiveCommands(
            ! app()->environment('testing') && ! $allowDestructive,
        );

        Event::listen(CommandStarting::class, BackupBeforeDestructiveDatabaseCommand::class);
    }

    /**
     * Configure API and authentication rate limiters.
     */
    protected function configureRateLimiting(): void
    {
        RateLimiter::for('api', function (Request $request) {
            $limit = (int) config('api.rate_limits.api', 60);

            return Limit::perMinute($limit)->by(
                $request->user()?->getAuthIdentifier() ?: $request->ip()
            );
        });

        RateLimiter::for('api-auth', function (Request $request) {
            $limit = (int) config('api.rate_limits.auth', 10);

            return Limit::perMinute($limit)->by(
                strtolower((string) $request->input('email')).'|'.$request->ip()
            );
        });

        RateLimiter::for('api-write', function (Request $request) {
            $limit = (int) config('api.rate_limits.write', 30);

            return Limit::perMinute($limit)->by(
                ($request->user()?->getAuthIdentifier() ?: $request->ip()).'|api-write'
            );
        });

        RateLimiter::for('api-workflow', function (Request $request) {
            $limit = (int) config('api.rate_limits.workflow', 30);

            return Limit::perMinute($limit)->by(
                ($request->user()?->getAuthIdentifier() ?: $request->ip()).'|api-workflow'
            );
        });

        RateLimiter::for('api-posting', function (Request $request) {
            $limit = (int) config('api.rate_limits.posting', 20);

            return Limit::perMinute($limit)->by(
                ($request->user()?->getAuthIdentifier() ?: $request->ip()).'|api-posting'
            );
        });

        RateLimiter::for('exports', function (Request $request) {
            return Limit::perMinute(10)->by(
                ($request->user()?->getAuthIdentifier() ?: $request->ip()).'|exports'
            );
        });

        RateLimiter::for('prints', function (Request $request) {
            return Limit::perMinute(30)->by(
                ($request->user()?->getAuthIdentifier() ?: $request->ip()).'|prints'
            );
        });

        RateLimiter::for('uploads', function (Request $request) {
            return Limit::perMinute(20)->by(
                ($request->user()?->getAuthIdentifier() ?: $request->ip()).'|uploads'
            );
        });

        RateLimiter::for('settings', function (Request $request) {
            return Limit::perMinute(60)->by(
                ($request->user()?->getAuthIdentifier() ?: $request->ip()).'|settings'
            );
        });
    }

    protected function configurePerformanceInstrumentation(): void
    {
        if (! config('performance.database.log_slow_queries', false)
            && ! config('performance.database.log_query_count', false)
            && ! config('performance.instrumentation.enabled', false)) {
            return;
        }

        DB::listen(function ($query): void {
            if (config('performance.database.log_query_count', false)
                || config('performance.instrumentation.enabled', false)) {
                RequestLogContext::incrementQueryCount();
            }

            if (! config('performance.database.log_slow_queries', false)) {
                return;
            }

            $threshold = (int) config('performance.database.slow_query_ms', 500);

            if ($query->time < $threshold) {
                return;
            }

            Log::warning('database.slow_query', array_merge(RequestLogContext::base(), [
                'duration_ms' => round((float) $query->time, 2),
                'sql' => $query->sql,
                'connection' => $query->connectionName,
            ]));
        });
    }

    protected function configureAccounting(): void
    {
        $observer = AccountingDocumentObserver::class;

        foreach ([Invoice::class, Bill::class, Payment::class, Expense::class, Payroll::class] as $model) {
            $model::observe($observer);
        }
    }
}
