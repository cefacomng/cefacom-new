<?php

namespace App\Http\Controllers;

use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class FoundationController extends Controller
{
    public function __invoke(Request $request): Response|RedirectResponse
    {
        if ($request->user()) {
            return redirect($request->user()->homePath());
        }

        return Inertia::render('Foundation', [
            'appName' => config('app.name'),
            'designSystem' => app()->environment(['local', 'testing']),
        ]);
    }
}
