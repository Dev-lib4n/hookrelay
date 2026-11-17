<?php

declare(strict_types=1);

namespace App\Rules;

use Closure;
use Illuminate\Contracts\Validation\ValidationRule;

class SafeExternalUrl implements ValidationRule
{
    public function __construct(
        private readonly bool $enforceInTesting = false,
    ) {}

    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        if (! is_string($value) || filter_var($value, FILTER_VALIDATE_URL) === false) {
            $fail('The :attribute must be a valid URL.');

            return;
        }

        /** @var array<string, int|string>|false $parts */
        $parts = parse_url($value);

        if ($parts === false || empty($parts['scheme']) || empty($parts['host'])) {
            $fail('The :attribute must include a valid scheme and host.');

            return;
        }

        $scheme = strtolower((string) $parts['scheme']);
        if (! in_array($scheme, ['http', 'https'], true)) {
            $fail('The :attribute must use HTTP or HTTPS.');

            return;
        }

        if (app()->environment('testing') && ! $this->enforceInTesting) {
            return;
        }

        $host = strtolower(trim((string) $parts['host'], '[]'));

        if (in_array($host, ['localhost', 'localhost.localdomain', 'ip6-localhost', 'ip6-loopback'], true)) {
            $fail('The :attribute cannot point to a local or internal host.');

            return;
        }

        $ips = $this->resolveHostIps($host);

        if ($ips === []) {
            $fail('The :attribute host could not be resolved.');

            return;
        }

        foreach ($ips as $ip) {
            if (! $this->isPublicIp($ip)) {
                $fail('The :attribute cannot target private, loopback, or reserved IP addresses.');

                return;
            }
        }
    }

    /**
     * @return list<string>
     */
    private function resolveHostIps(string $host): array
    {
        if (filter_var($host, FILTER_VALIDATE_IP) !== false) {
            return [$host];
        }

        $ips = [];

        $ipv4Records = @gethostbynamel($host);
        if (is_array($ipv4Records)) {
            foreach ($ipv4Records as $ip) {
                $ips[] = $ip;
            }
        }

        $aaaaRecords = @dns_get_record($host, DNS_AAAA);
        if (is_array($aaaaRecords)) {
            foreach ($aaaaRecords as $record) {
                if (isset($record['ipv6']) && is_string($record['ipv6'])) {
                    $ips[] = $record['ipv6'];
                }
            }
        }

        return array_values(array_unique($ips));
    }

    private function isPublicIp(string $ip): bool
    {
        return filter_var(
            $ip,
            FILTER_VALIDATE_IP,
            FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE
        ) !== false;
    }
}
