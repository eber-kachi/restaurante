<?php

namespace Modules\ErrorTracker\Providers;

use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Contracts\Debug\ExceptionHandler;
use Illuminate\Log\Events\MessageLogged;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\ServiceProvider;
use Modules\ErrorTracker\Console\PruneErrorEventsCommand;
use Modules\ErrorTracker\Console\TestErrorTrackerCommand;
use Modules\ErrorTracker\Services\ErrorTracker;
use Throwable;

class ErrorTrackerServiceProvider extends ServiceProvider
{
    protected string $name = 'ErrorTracker';

    protected string $nameLower = 'errortracker';

    public function register(): void
    {
        $this->mergeConfigFrom(module_path($this->name, 'Config/config.php'), $this->nameLower);

        $this->app->singleton(ErrorTracker::class);
    }

    public function boot(): void
    {
        $this->loadMigrationsFrom(module_path($this->name, 'Database/Migrations'));
        $this->loadViewsFrom(module_path($this->name, 'Resources/views'), $this->nameLower);

        $this->registerRoutes();
        $this->registerCommands();
        $this->registerHooks();
    }

    /**
     * Hooks are always registered; ErrorTracker::isActive() decides at capture time
     * (production only by default), so config changes do not need a code change.
     */
    protected function registerHooks(): void
    {
        // 1) Unhandled exceptions + every report($e) call.
        try {
            $handler = $this->app->make(ExceptionHandler::class);

            if (method_exists($handler, 'reportable')) {
                $handler->reportable(function (Throwable $e) {
                    $this->app->make(ErrorTracker::class)->captureException($e);
                    // returning nothing => Laravel keeps its normal logging
                });
            }
        } catch (Throwable $e) {
            @error_log('[ErrorTracker] could not attach to exception handler: ' . $e->getMessage());
        }

        // 2) Log::error()/critical()/... — where most try/catch blocks hide failures.
        Event::listen(MessageLogged::class, function (MessageLogged $event) {
            $this->app->make(ErrorTracker::class)->captureLog($event);
        });
    }

    protected function registerRoutes(): void
    {
        if ($this->app->routesAreCached()) {
            return;
        }

        Route::middleware('web')->group(module_path($this->name, 'Routes/web.php'));
    }

    protected function registerCommands(): void
    {
        if (! $this->app->runningInConsole()) {
            return;
        }

        $this->commands([
            TestErrorTrackerCommand::class,
            PruneErrorEventsCommand::class,
        ]);

        $this->app->booted(function () {
            $this->app->make(Schedule::class)->command('error-tracker:prune')->daily();
        });
    }
}
