<?php

namespace App\Http\Middleware;

use App\Support\Navigation;
use Illuminate\Http\Request;
use Inertia\Middleware;

class HandleInertiaRequests extends Middleware
{
    protected $rootView = 'app';

    public function version(Request $request): ?string
    {
        return parent::version($request);
    }

    /**
     * Shared props. Never put secrets or customer-owned data here.
     *
     * @return array<string, mixed>
     */
    public function share(Request $request): array
    {
        $user = $request->user();

        return [
            ...parent::share($request),
            'app' => ['name' => config('app.name')],
            'auth' => ['user' => $user ? [
                'name' => $user->name,
                'email' => $user->email,
                'businessName' => $user->business_name,
                'isStaff' => $user->isStaff(),
                'emailVerified' => $user->hasVerifiedEmail(),
                'roles' => $user->roles->pluck('name')->values()->all(),
            ] : null],
            'navigation' => fn () => $user === null ? null : ($user->isStaff()
                ? ['admin' => Navigation::admin($user)]
                : ['customer' => Navigation::customer()]),
            'flash' => ['status' => fn () => $request->session()->get('status')],
            // Batch 4 supplies ['availableKobo' => int, 'reservedKobo' => int] for the signed-in customer.
            'wallet' => null,
        ];
    }
}
