<?php

declare(strict_types=1);

namespace App\Providers;

use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Support\Facades\Gate;
use Laravel\Horizon\HorizonApplicationServiceProvider;

class HorizonServiceProvider extends HorizonApplicationServiceProvider
{
    public function boot(): void
    {
        parent::boot();
    }

    protected function gate(): void
    {
        Gate::define('viewHorizon', function (?Authenticatable $user = null): bool {
            if ($this->app->environment('local')) {
                return true;
            }

            if ($user === null || ! isset($user->email)) {
                return false;
            }

            /** @var list<string> $allowedEmails */
            $allowedEmails = config('webhooks.horizon_allowed_emails', []);

            return in_array((string) $user->email, $allowedEmails, true);
        });
    }
}
