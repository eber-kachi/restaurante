<?php

namespace Modules\ErrorTracker\Services;

use Illuminate\Log\Events\MessageLogged;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Schema;
use Modules\ErrorTracker\Mail\ErrorOccurredMail;
use Modules\ErrorTracker\Models\ErrorEvent;
use Throwable;
use WeakMap;

/**
 * Sentry-like error tracker.
 *
 * Design rules:
 *  - It must NEVER throw: every step is wrapped and failures go to error_log(), not to
 *    Laravel's logger (that would re-enter the tracker).
 *  - It must keep working when the DB or cache are down: throttling and the recipient
 *    list fall back to plain files.
 */
class ErrorTracker
{
    private static bool $capturing = false;

    private static ?bool $tableExists = null;

    /** @var WeakMap<Throwable, bool> Exceptions already captured in this process. */
    private WeakMap $seen;

    private bool $forceActive = false;

    public function __construct()
    {
        $this->seen = new WeakMap();
    }

    public function isActive(): bool
    {
        if ($this->forceActive) {
            return true;
        }

        if (! config('errortracker.enabled', true)) {
            return false;
        }

        return in_array(app()->environment(), (array) config('errortracker.environments', ['production']), true);
    }

    /** Used by `error-tracker:test --force` to try it outside production. */
    public function forceActive(bool $value = true): void
    {
        $this->forceActive = $value;
    }

    /**
     * Entry point for the exception handler (unhandled exceptions and report($e)).
     */
    public function captureException(Throwable $e, array $extra = [], string $level = 'error'): void
    {
        if (self::$capturing || ! $this->isActive() || isset($this->seen[$e])) {
            return;
        }

        $this->seen[$e] = true;

        if ($this->shouldIgnore($e)) {
            return;
        }

        $this->guard(function () use ($e, $extra, $level) {
            [$appFile, $appLine] = $this->firstAppFrame($e->getTrace(), $e->getFile(), $e->getLine());

            $payload = [
                'source' => 'exception',
                'level' => $level,
                'exception_class' => get_class($e),
                'message' => $this->limit($e->getMessage() !== '' ? $e->getMessage() : '(no message)', 5000),
                'file' => $this->relative($appFile),
                'line' => $appLine,
                'trace' => $this->formatTrace($e),
                'fingerprint' => sha1(implode('|', [
                    get_class($e),
                    $this->relative($e->getFile()),
                    $e->getLine(),
                    $this->relative($appFile),
                    $appLine,
                ])),
                'context' => $this->buildContext($extra),
            ];

            $this->process($payload);
        });
    }

    /**
     * Entry point for Log::error()/critical()/... calls.
     */
    public function captureLog(MessageLogged $event): void
    {
        if (self::$capturing || ! $this->isActive() || ! config('errortracker.capture_logs', true)) {
            return;
        }

        if (! in_array($event->level, (array) config('errortracker.log_levels', []), true)) {
            return;
        }

        $context = is_array($event->context) ? $event->context : [];

        // Log::error('...', ['exception' => $e]) or the handler's own log line.
        if (($context['exception'] ?? null) instanceof Throwable) {
            $exception = $context['exception'];
            unset($context['exception']);

            $this->captureException($exception, [
                'log_message' => (string) $event->message,
                'log_context' => $context,
            ], $event->level);

            return;
        }

        $message = (string) $event->message;

        if ($this->messageIgnored($message)) {
            return;
        }

        $this->guard(function () use ($event, $message, $context) {
            $frames = debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS, 60);
            [$file, $line] = $this->firstAppFrame($frames, null, null);

            $payload = [
                'source' => 'log',
                'level' => $event->level,
                'exception_class' => null,
                'message' => $this->limit($message, 5000),
                'file' => $this->relative($file),
                'line' => $line,
                'trace' => $this->formatFrames($frames),
                'fingerprint' => sha1(implode('|', [
                    'log',
                    $event->level,
                    $this->relative($file),
                    $line,
                    $this->normalizeMessage($message),
                ])),
                'context' => $this->buildContext(['log_context' => $context]),
            ];

            $this->process($payload);
        });
    }

    /* ------------------------------------------------------------------ */
    /*  Pipeline                                                           */
    /* ------------------------------------------------------------------ */

    private function process(array $payload): void
    {
        $now = now();
        $reason = null;
        $occurrences = 1;
        $sinceNotified = 1;
        $event = null;
        $cooldown = max(1, (int) config('errortracker.notify.cooldown_minutes', 60));

        try {
            if ($this->tableExists()) {
                $event = ErrorEvent::where('fingerprint', $payload['fingerprint'])->first();

                if (! $event) {
                    $event = ErrorEvent::create([
                        'fingerprint' => $payload['fingerprint'],
                        'source' => $payload['source'],
                        'level' => $payload['level'],
                        'exception_class' => $payload['exception_class'],
                        'message' => $payload['message'],
                        'file' => $payload['file'],
                        'line' => $payload['line'],
                        'trace' => $payload['trace'],
                        'context' => $payload['context'],
                        'environment' => app()->environment(),
                        'occurrences' => 1,
                        'occurrences_since_notified' => 1,
                        'first_seen_at' => $now,
                        'last_seen_at' => $now,
                    ]);
                    $reason = 'new';
                } else {
                    $event->occurrences++;
                    $event->occurrences_since_notified++;
                    $event->last_seen_at = $now;
                    // Keep the latest sample so the dashboard shows fresh request data.
                    $event->message = $payload['message'];
                    $event->trace = $payload['trace'];
                    $event->context = $payload['context'];

                    if ($event->resolved_at) {
                        $event->resolved_at = null;
                        $reason = 'regression';
                    } elseif (! $event->last_notified_at || $event->last_notified_at->lt($now->copy()->subMinutes($cooldown))) {
                        $reason = 'repeat';
                    }
                }

                $occurrences = $event->occurrences;
                $sinceNotified = $event->occurrences_since_notified;

                if ($reason) {
                    $event->last_notified_at = $now;
                    $event->occurrences_since_notified = 0;
                }

                $event->save();
            } else {
                $reason = $this->fileThrottleAllows($payload['fingerprint'], $cooldown) ? 'new' : null;
            }
        } catch (Throwable $storeError) {
            // DB unavailable (often the very error we are reporting): fall back to files.
            $this->internalError('store', $storeError);
            $event = null;
            $reason = $this->fileThrottleAllows($payload['fingerprint'], $cooldown) ? 'new' : null;
            $payload['context']['tracker_note'] = 'Could not store event in database: ' . $storeError->getMessage();
        }

        if ($reason && config('errortracker.notify.mail', true)) {
            $this->notify($payload, $reason, $occurrences, $sinceNotified, $event?->id);
        }
    }

    private function notify(array $payload, string $reason, int $occurrences, int $sinceNotified, ?int $eventId): void
    {
        if (! $this->hourlyBudgetAllows()) {
            return;
        }

        $recipients = $this->recipients();

        if (empty($recipients)) {
            $this->internalError('notify', new \RuntimeException('No superadmin recipients found'));

            return;
        }

        $mail = new ErrorOccurredMail($payload, $reason, $occurrences, $sinceNotified, $eventId);

        foreach ($recipients as $email) {
            try {
                Mail::to($email)->send(clone $mail);
            } catch (Throwable $mailError) {
                $this->internalError('mail', $mailError);

                return; // mailer is broken, no point trying the rest
            }
        }
    }

    /* ------------------------------------------------------------------ */
    /*  Recipients                                                         */
    /* ------------------------------------------------------------------ */

    /** @return string[] */
    public function recipients(): array
    {
        $cacheFile = $this->storageDir() . '/recipients.json';
        $emails = [];

        try {
            // Superadmins are users without restaurant (same rule as the SuperAdmin middleware).
            $emails = DB::table('users')
                ->whereNull('restaurant_id')
                ->whereNotNull('email')
                ->pluck('email')
                ->all();

            @file_put_contents($cacheFile, json_encode($emails), LOCK_EX);
        } catch (Throwable $e) {
            if (is_file($cacheFile)) {
                $emails = json_decode((string) @file_get_contents($cacheFile), true) ?: [];
            }
        }

        $emails = array_merge($emails, (array) config('errortracker.notify.extra_emails', []));

        return array_values(array_unique(array_filter(
            array_map(fn ($e) => strtolower(trim((string) $e)), $emails),
            fn ($e) => filter_var($e, FILTER_VALIDATE_EMAIL)
        )));
    }

    /* ------------------------------------------------------------------ */
    /*  Throttling (file based, independent from DB/cache)                 */
    /* ------------------------------------------------------------------ */

    private function fileThrottleAllows(string $fingerprint, int $cooldownMinutes): bool
    {
        $file = $this->storageDir() . '/fp-' . $fingerprint;

        if (is_file($file) && (time() - (int) @filemtime($file)) < $cooldownMinutes * 60) {
            return false;
        }

        @touch($file);

        return true;
    }

    private function hourlyBudgetAllows(): bool
    {
        $max = (int) config('errortracker.notify.max_per_hour', 30);

        if ($max <= 0) {
            return true;
        }

        $file = $this->storageDir() . '/budget-' . date('YmdH');
        $handle = @fopen($file, 'c+');

        if (! $handle) {
            return true;
        }

        try {
            flock($handle, LOCK_EX);
            $count = (int) stream_get_contents($handle);

            if ($count >= $max) {
                return false;
            }

            ftruncate($handle, 0);
            rewind($handle);
            fwrite($handle, (string) ($count + 1));

            return true;
        } finally {
            flock($handle, LOCK_UN);
            fclose($handle);
        }
    }

    /* ------------------------------------------------------------------ */
    /*  Context                                                            */
    /* ------------------------------------------------------------------ */

    private function buildContext(array $extra = []): array
    {
        $context = [
            'app' => [
                'name' => config('app.name'),
                'url' => config('app.url'),
                'environment' => app()->environment(),
                'host' => gethostname() ?: null,
                'php' => PHP_VERSION,
                'laravel' => app()->version(),
                'memory_peak_mb' => round(memory_get_peak_usage(true) / 1048576, 1),
            ],
        ];

        try {
            if (app()->runningInConsole()) {
                $context['console'] = [
                    'command' => implode(' ', array_slice($_SERVER['argv'] ?? [], 0, 10)),
                ];
            } elseif (app()->bound('request')) {
                $request = request();
                $context['request'] = [
                    'method' => $request->method(),
                    'url' => $this->limit($request->fullUrl(), 1000),
                    'route' => optional($request->route())->getName(),
                    'ip' => $request->ip(),
                    'user_agent' => $this->limit((string) $request->userAgent(), 300),
                    'referer' => $this->limit((string) $request->headers->get('referer'), 500),
                    'input' => $this->sanitize($request->except(['components'])),
                ];

                // Livewire: name the component(s) and method(s) involved.
                if ($request->has('components')) {
                    $context['livewire'] = collect((array) $request->input('components'))
                        ->map(function ($component) {
                            $snapshot = json_decode($component['snapshot'] ?? '{}', true) ?: [];

                            return [
                                'component' => $snapshot['memo']['name'] ?? null,
                                'path' => $snapshot['memo']['path'] ?? null,
                                'calls' => collect($component['calls'] ?? [])->pluck('method')->all(),
                                'updates' => array_keys((array) ($component['updates'] ?? [])),
                            ];
                        })->all();
                }
            }
        } catch (Throwable $e) {
            $context['request_error'] = $e->getMessage();
        }

        try {
            // Only read the user if the guard already resolved it: avoids extra DB queries
            // when the DB is the thing that is failing.
            $guard = auth()->guard();
            if (method_exists($guard, 'hasUser') && $guard->hasUser()) {
                $user = $guard->user();
                $context['user'] = [
                    'id' => $user->id ?? null,
                    'name' => $user->name ?? null,
                    'email' => $user->email ?? null,
                    'restaurant_id' => $user->restaurant_id ?? null,
                    'branch_id' => $user->branch_id ?? null,
                ];
            }
        } catch (Throwable $e) {
            // ignore: user context is optional
        }

        foreach ($extra as $key => $value) {
            $context[$key] = $this->sanitize($value);
        }

        return $context;
    }

    private function sanitize($value, int $depth = 0)
    {
        if ($depth > 5) {
            return '[depth limit]';
        }

        if ($value instanceof Throwable) {
            return get_class($value) . ': ' . $this->limit($value->getMessage(), 1000);
        }

        if (is_object($value)) {
            if (method_exists($value, 'toArray')) {
                try {
                    $value = $value->toArray();
                } catch (Throwable $e) {
                    return get_class($value);
                }
            } elseif (method_exists($value, '__toString')) {
                return $this->limit((string) $value, 2000);
            } else {
                return get_class($value);
            }
        }

        if (is_array($value)) {
            $sensitive = array_map('strtolower', (array) config('errortracker.sensitive_keys', []));
            $out = [];
            $i = 0;

            foreach ($value as $key => $item) {
                if (++$i > 100) {
                    $out['…'] = 'truncated';
                    break;
                }

                if (is_string($key) && in_array(strtolower($key), $sensitive, true)) {
                    $out[$key] = '********';
                    continue;
                }

                $out[$key] = $this->sanitize($item, $depth + 1);
            }

            return $out;
        }

        if (is_string($value)) {
            return $this->limit($value, 2000);
        }

        if (is_resource($value)) {
            return 'resource';
        }

        return $value;
    }

    /* ------------------------------------------------------------------ */
    /*  Helpers                                                            */
    /* ------------------------------------------------------------------ */

    private function shouldIgnore(Throwable $e): bool
    {
        foreach ((array) config('errortracker.ignore_exceptions', []) as $class) {
            if ($e instanceof $class) {
                return true;
            }
        }

        // Errors raised by this module itself must never loop back.
        if (str_contains(str_replace('\\', '/', $e->getFile()), 'Modules/ErrorTracker/')) {
            return true;
        }

        return $this->messageIgnored($e->getMessage());
    }

    private function messageIgnored(string $message): bool
    {
        foreach ((array) config('errortracker.ignore_messages', []) as $pattern) {
            if (@preg_match($pattern, $message)) {
                return true;
            }
        }

        return false;
    }

    /**
     * First frame that belongs to the application (not vendor, not this module).
     *
     * @return array{0: ?string, 1: ?int}
     */
    private function firstAppFrame(array $frames, ?string $file, ?int $line): array
    {
        if ($file && $this->isAppFile($file)) {
            return [$file, $line];
        }

        foreach ($frames as $frame) {
            if (! empty($frame['file']) && $this->isAppFile($frame['file'])) {
                return [$frame['file'], $frame['line'] ?? null];
            }
        }

        return [$file, $line];
    }

    private function isAppFile(string $file): bool
    {
        $file = str_replace('\\', '/', $file);

        if (str_contains($file, '/storage/framework/views/')) {
            return true; // compiled blade views are app code too
        }

        return ! str_contains($file, '/vendor/') && ! str_contains($file, 'Modules/ErrorTracker/');
    }

    private function formatTrace(Throwable $e): string
    {
        $lines = [];
        $current = $e;
        $depth = 0;

        while ($current && $depth < 3) {
            if ($depth > 0) {
                $lines[] = '';
                $lines[] = 'Caused by: ' . get_class($current) . ': ' . $this->limit($current->getMessage(), 1000);
            }

            $lines[] = '#  ' . $this->relative($current->getFile()) . ':' . $current->getLine();
            $lines[] = $this->formatFrames($current->getTrace());

            $current = $current->getPrevious();
            $depth++;
        }

        return implode("\n", $lines);
    }

    private function formatFrames(array $frames): string
    {
        $max = (int) config('errortracker.max_trace_frames', 40);
        $out = [];

        foreach (array_slice($frames, 0, $max) as $i => $frame) {
            $where = isset($frame['file']) ? $this->relative($frame['file']) . ':' . ($frame['line'] ?? '?') : '[internal]';
            $call = ($frame['class'] ?? '') . ($frame['type'] ?? '') . ($frame['function'] ?? '');
            $vendor = isset($frame['file']) && str_contains(str_replace('\\', '/', $frame['file']), '/vendor/') ? ' [vendor]' : '';
            $out[] = "#{$i} {$where} {$call}(){$vendor}";
        }

        if (count($frames) > $max) {
            $out[] = '… ' . (count($frames) - $max) . ' more frames';
        }

        return implode("\n", $out);
    }

    private function normalizeMessage(string $message): string
    {
        $message = preg_replace('/[0-9a-f]{8}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{12}/i', '{uuid}', $message);
        $message = preg_replace('/\d+/', '#', $message);

        return mb_substr($message, 0, 200);
    }

    private function relative(?string $file): ?string
    {
        if (! $file) {
            return null;
        }

        $base = str_replace('\\', '/', base_path()) . '/';

        return str_replace($base, '', str_replace('\\', '/', $file));
    }

    private function limit(?string $value, int $max): string
    {
        $value = (string) $value;

        return mb_strlen($value) > $max ? mb_substr($value, 0, $max) . '…' : $value;
    }

    private function tableExists(): bool
    {
        if (self::$tableExists === null) {
            try {
                self::$tableExists = Schema::hasTable('error_tracker_events');
            } catch (Throwable $e) {
                return false; // DB down right now; re-check next time
            }
        }

        return self::$tableExists;
    }

    private function storageDir(): string
    {
        $dir = (string) config('errortracker.storage_path', storage_path('framework/error-tracker'));

        if (! is_dir($dir)) {
            @mkdir($dir, 0775, true);
        }

        return $dir;
    }

    private function guard(callable $callback): void
    {
        self::$capturing = true;

        try {
            $callback();
        } catch (Throwable $e) {
            $this->internalError('capture', $e);
        } finally {
            self::$capturing = false;
        }
    }

    private function internalError(string $stage, Throwable $e): void
    {
        // Plain PHP error_log on purpose: Laravel's logger would re-enter the tracker.
        @error_log('[ErrorTracker:' . $stage . '] ' . get_class($e) . ': ' . $e->getMessage() . ' @ ' . $e->getFile() . ':' . $e->getLine());
    }
}
