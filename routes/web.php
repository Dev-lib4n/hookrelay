<?php

declare(strict_types=1);

use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Route;

Route::get('/', function (): JsonResponse {
    return response()->json([
        'service' => config('app.name', 'HookRelay'),
        'package' => 'laravel-webhook-broker',
        'status' => 'operational',
        'version' => '1.0.0',
    ]);
});
