<?php

declare(strict_types=1);

use App\Services\WebhookSignatureService;

it('generates and verifies replay-resistant HMAC-SHA256 signatures accurately', function (): void {
    $service = new WebhookSignatureService;
    $payload = '{"order_id":"ord_123"}';
    $secret = 'whsec_unit_test_secret';
    $timestamp = now()->timestamp;

    $signature = $service->sign($payload, $secret, $timestamp);

    expect($signature)->toBe(hash_hmac('sha256', "{$timestamp}.{$payload}", $secret))
        ->and($service->verify($payload, $secret, $timestamp, $signature))->toBeTrue()
        ->and($service->verify($payload, 'wrong_secret', $timestamp, $signature))->toBeFalse()
        ->and($service->verify($payload, $secret, $timestamp - 600, $signature, toleranceSeconds: 300))->toBeFalse();
});
