<?php

declare(strict_types=1);

use Illuminate\Support\Str;

return [
    'domain' => env('HORIZON_DOMAIN'),
    'path' => env('HORIZON_PATH', 'horizon'),
    'use' => 'default',
    'prefix' => env(
        'HORIZON_PREFIX',
        Str::slug((string) env('APP_NAME', 'hookrelay'), '_').'_horizon:'
    ),
    'middleware' => ['web'],
    'waits' => [
        'redis:webhooks' => 60,
    ],
    'trim' => [
        'recent' => 60,
        'pending' => 60,
        'completed' => 60,
        'recent_failed' => 10080,
        'failed' => 10080,
        'monitored' => 10080,
    ],
    'silenced' => [],
    'metrics' => [
        'trim_snapshots' => [
            'job' => 24,
            'queue' => 24,
        ],
    ],
    'fast_termination' => false,
    'memory_limit' => 128,
    'defaults' => [
        'supervisor-webhooks' => [
            'connection' => 'redis',
            'queue' => ['webhooks', 'default'],
            'balance' => 'auto',
            'autoScalingStrategy' => 'time',
            'maxProcesses' => 5,
            'maxTime' => 0,
            'maxJobs' => 0,
            'memory' => 128,
            'tries' => 25,
            'timeout' => 65,
            'nice' => 0,
        ],
    ],
    'environments' => [
        'production' => [
            'supervisor-webhooks' => [
                'maxProcesses' => 10,
                'balanceMaxShift' => 1,
                'balanceCooldown' => 3,
            ],
        ],
        'local' => [
            'supervisor-webhooks' => [
                'maxProcesses' => 3,
            ],
        ],
    ],
];
