<?php

declare(strict_types=1);

return [
    /*
    |--------------------------------------------------------------------------
    | Optional Global API Key
    |--------------------------------------------------------------------------
    |
    | If configured via HOOKRELAY_API_KEY, requests to /api/v1/* must include
    | this key in the X-Api-Key header or Authorization Bearer token.
    |
    */
    'api_key' => env('HOOKRELAY_API_KEY'),

    /*
    |--------------------------------------------------------------------------
    | Exponential Backoff Schedule (in seconds)
    |--------------------------------------------------------------------------
    */
    'backoff' => [10, 60, 300, 900, 3600],

    /*
    |--------------------------------------------------------------------------
    | Sensitive Headers Redaction (LGPD / Privacy by Design)
    |--------------------------------------------------------------------------
    */
    'sensitive_headers' => [
        'authorization',
        'cookie',
        'set-cookie',
        'x-api-key',
        'proxy-authorization',
    ],

    /*
    |--------------------------------------------------------------------------
    | Data Retention Policy (in days)
    |--------------------------------------------------------------------------
    */
    'retention_days' => (int) env('WEBHOOK_RETENTION_DAYS', 30),

    /*
    |--------------------------------------------------------------------------
    | Maximum Stored Response Body Size (in characters)
    |--------------------------------------------------------------------------
    */
    'max_response_body_length' => 10000,

    /*
    |--------------------------------------------------------------------------
    | Allowed Horizon Dashboard Emails in Non-Local Environments
    |--------------------------------------------------------------------------
    */
    'horizon_allowed_emails' => array_values(array_filter(
        array_map('trim', explode(',', (string) env('HORIZON_ALLOWED_EMAILS', '')))
    )),
];
