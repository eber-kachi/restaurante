<?php

namespace Modules\ErrorTracker\Console;

use Illuminate\Console\Command;
use Modules\ErrorTracker\Services\ErrorTracker;

class TestErrorTrackerCommand extends Command
{
    protected $signature = 'error-tracker:test {--force : Run even if APP_ENV is not production}';

    protected $description = 'Send a test error to the ErrorTracker and email all superadmins.';

    public function handle(ErrorTracker $tracker): int
    {
        if (! $tracker->isActive() && ! $this->option('force')) {
            $this->warn('ErrorTracker is inactive in "' . app()->environment() . '" (only: ' . implode(', ', config('errortracker.environments')) . ').');
            $this->line('Use --force to test anyway.');

            return self::FAILURE;
        }

        $tracker->forceActive((bool) $this->option('force'));

        $recipients = $tracker->recipients();
        $this->info('Recipients: ' . (implode(', ', $recipients) ?: '(none!)'));

        // Unique message => new fingerprint => always notifies (subject to the hourly cap).
        report(new \RuntimeException('ErrorTracker test exception ' . now()->toDateTimeString()));

        $tracker->forceActive(false);

        $this->info('Test error reported. Check the inbox and /error-tracker.');

        return self::SUCCESS;
    }
}
