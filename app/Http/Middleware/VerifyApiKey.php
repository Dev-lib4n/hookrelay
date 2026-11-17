<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class VerifyApiKey
{
    /**
     * @param  Closure(Request): Response  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $configuredKey = config('webhooks.api_key');

        if (! is_string($configuredKey) || trim($configuredKey) === '') {
            return $next($request);
        }

        $providedKey = $request->header('X-Api-Key') ?? $request->bearerToken();

        if (! is_string($providedKey) || ! hash_equals($configuredKey, $providedKey)) {
            return response()->json([
                'message' => 'Unauthorized. Invalid or missing API key.',
            ], Response::HTTP_UNAUTHORIZED);
        }

        return $next($request);
    }
}
