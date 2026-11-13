<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Request;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->web(append: [
            \App\Http\Middleware\HandleInertiaRequests::class,
        ]);

        $middleware->alias([
            'customer' => \App\Http\Middleware\EnsureCustomer::class,
            'staff' => \App\Http\Middleware\EnsureStaff::class,
        ]);

        $middleware->redirectUsersTo(fn (\Illuminate\Http\Request $request) => $request->user()?->homePath() ?? '/');

        // Behind a load balancer or reverse proxy, list its address(es) in TRUSTED_PROXIES (comma separated).
        $middleware->trustProxies(at: env('TRUSTED_PROXIES') ? array_map('trim', explode(',', (string) env('TRUSTED_PROXIES'))) : null);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        $exceptions->shouldRenderJsonWhen(
            fn (Request $request) => $request->is('api/*') || $request->expectsJson(),
        );
    })->create();
