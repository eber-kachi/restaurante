<?php

namespace Modules\ErrorTracker\Console;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Schema;
use Modules\ErrorTracker\Models\ErrorEvent;

class PruneErrorEventsCommand extends Command
{
    protected $signature = 'error-tracker:prune {--days= : Override retention days}';

    protected $description = 'Delete error events not seen within the retention period and old throttle files.';

    public function handle(): int
    {
        $days = (int) ($this->option('days') ?: config('errortracker.retention_days', 90));

        if (Schema::hasTable('error_tracker_events')) {
            $deleted = ErrorEvent::where('last_seen_at', '<', now()->subDays($days))->delete();
            $this->info("Deleted {$deleted} error events older than {$days} days.");
        }

        $dir = config('errortracker.storage_path');
        if (is_dir($dir)) {
            $files = array_merge(glob($dir . '/fp-*') ?: [], glob($dir . '/budget-*') ?: []);
            foreach ($files as $file) {
                if (filemtime($file) < time() - 2 * 86400) {
                    @unlink($file);
                }
            }
        }

        return self::SUCCESS;
    }
}
