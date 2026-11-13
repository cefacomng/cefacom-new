<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\LoginRequest;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;
use Inertia\Response;

class LoginController extends Controller
{
    public function create(): Response
    {
        return Inertia::render('auth/Login');
    }

    public function store(LoginRequest $request): RedirectResponse
    {
        $request->authenticate();
        $request->session()->regenerate();

        $user = $request->user();

        // Staff and customers have separate areas; a stored "intended" URL for the other area is ignored.
        $area = $user->isStaff() ? '/admin' : '/app';

        return redirect($this->safeIntendedPath($request->session()->pull('url.intended'), $area) ?? $area);
    }

    /**
     * Returns only a local path (plus query) inside the given area, never a full URL, so a
     * crafted intended URL can never send someone to another site.
     */
    private function safeIntendedPath(mixed $url, string $area): ?string
    {
        if (! is_string($url)) {
            return null;
        }

        $parts = parse_url($url);
        $path = is_array($parts) ? ($parts['path'] ?? '') : '';

        if (($path !== $area && ! str_starts_with($path, $area.'/')) || str_contains($path, '..') || str_contains($path, '\\')) {
            return null;
        }

        return $path.(isset($parts['query']) ? '?'.$parts['query'] : '');
    }

    public function destroy(Request $request): RedirectResponse
    {
        Auth::guard('web')->logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect('/');
    }
}
