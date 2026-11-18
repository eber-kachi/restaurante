<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Master switch
    |--------------------------------------------------------------------------
    | Even when enabled, nothing is captured unless APP_ENV is in "environments".
    */
    'enabled' => env('ERROR_TRACKER_ENABLED', true),

    /*
    | Only these APP_ENV values activate the tracker (production only by default).
    */
    'environments' => array_filter(array_map('trim', explode(',', env('ERROR_TRACKER_ENVIRONMENTS', 'production,prod')))),

    /*
    |--------------------------------------------------------------------------
    | Notifications
    |--------------------------------------------------------------------------
    | Every user with restaurant_id = NULL (superadmin panel users) gets the email.
    | Extra comma separated addresses can be added with ERROR_TRACKER_EXTRA_EMAILS.
    */
    'notify' => [
        'mail' => env('ERROR_TRACKER_MAIL', true),

        'extra_emails' => array_filter(array_map('trim', explode(',', env('ERROR_TRACKER_EXTRA_EMAILS', '')))),

        // Same error is re-notified at most once per this many minutes.
        'cooldown_minutes' => (int) env('ERROR_TRACKER_COOLDOWN_MINUTES', 60),

        // Hard cap of emails per hour across all errors (protects against mail storms).
        'max_per_hour' => (int) env('ERROR_TRACKER_MAX_PER_HOUR', 30),
    ],

    /*
    |--------------------------------------------------------------------------
    | Log capture
    |--------------------------------------------------------------------------
    | Captures Log::error()/critical()/alert()/emergency() calls, which is where most
    | try/catch blocks hide failures. Set the minimum level to "warning" to also
    | capture Log::warning().
    */
    'capture_logs' => env('ERROR_TRACKER_CAPTURE_LOGS', true),

    'log_levels' => ['error', 'critical', 'alert', 'emergency'],

    /*
    |--------------------------------------------------------------------------
    | Ignore rules
    |--------------------------------------------------------------------------
    | Laravel already skips validation, auth, 404, CSRF, etc. Add more here.
    */
    'ignore_exceptions' => [
        // \Some\Exception::class,
    ],

    // Regex patterns matched against the message.
    'ignore_messages' => [
        // '/Broken pipe/i',
    ],

    /*
    |--------------------------------------------------------------------------
    | Storage
    |--------------------------------------------------------------------------
    */
    'retention_days' => (int) env('ERROR_TRACKER_RETENTION_DAYS', 90),

    'max_trace_frames' => 40,

    // Request input keys whose values are masked before storing.
    'sensitive_keys' => [
        'password', 'password_confirmation', 'current_password', 'token', '_token',
        'secret', 'api_key', 'apikey', 'key', 'authorization', 'card', 'card_number',
        'cvv', 'cvc', 'pin', 'otp', 'smtp_password', 'mail_password',
    ],

    // Directory for file-based throttling/recipient cache (works even if DB/cache is down).
    'storage_path' => storage_path('framework/error-tracker'),
];
