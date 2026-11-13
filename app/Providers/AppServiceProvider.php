<?php

namespace App\Providers;

use App\Enums\PermissionKey;
use App\Models\User;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\ServiceProvider;
use Illuminate\Validation\Rules\Password;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        //
    }

    public function boot(): void
    {
        if ($this->app->isProduction()) {
            URL::forceHttps();
        }

        // bcrypt silently ignores bytes past 72, so longer passwords are rejected outright.
        Password::defaults(fn () => Password::min(10)->max(72)->letters()->numbers());

        // One gate per permission: $user->can('pricing.change'), @can, Gate::authorize, can: middleware.
        foreach (PermissionKey::cases() as $permission) {
            Gate::define($permission->value, fn (User $user) => $user->hasPermission($permission));
        }

        RateLimiter::for('login', fn (Request $request) => Limit::perMinute(20)->by($request->ip()));
        RateLimiter::for('register', fn (Request $request) => Limit::perMinute(6)->by($request->ip()));
        RateLimiter::for('password-email', fn (Request $request) => Limit::perMinute(5)->by($request->ip()));
        RateLimiter::for('verification', fn (Request $request) => Limit::perMinute(6)->by($request->user()?->getAuthIdentifier() ?: $request->ip()));
    }
}
