<?php

declare(strict_types=1);

namespace App\Services;

class WebhookSignatureService
{
    /**
     * Generate a Replay-Attack resistant HMAC-SHA256 signature over "$timestamp.$rawPayload".
     */
    public function sign(string $rawPayload, string $secret, int|string $timestamp): string
    {
        $signedPayload = "{$timestamp}.{$rawPayload}";

        return hash_hmac('sha256', $signedPayload, $secret);
    }

    /**
     * Verify an incoming HMAC-SHA256 signature and optional timestamp tolerance window.
     */
    public function verify(
        string $rawPayload,
        string $secret,
        int|string $timestamp,
        string $signature,
        int $toleranceSeconds = 300,
    ): bool {
        if (! is_numeric($timestamp)) {
            return false;
        }

        $timestampInt = (int) $timestamp;
        if ($toleranceSeconds > 0 && abs(now()->timestamp - $timestampInt) > $toleranceSeconds) {
            return false;
        }

        $expected = $this->sign($rawPayload, $secret, $timestampInt);

        return hash_equals($expected, $signature);
    }
}
