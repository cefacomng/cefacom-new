<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\RegisterRequest;
use App\Models\User;
use Illuminate\Auth\Events\Registered;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Inertia\Inertia;
use Inertia\Response;
use Throwable;

class RegisterController extends Controller
{
    public function create(): Response
    {
        return Inertia::render('auth/Register');
    }

    public function store(RegisterRequest $request): RedirectResponse
    {
        // Registration always creates a customer. There is no input that can create or promote staff.
        $user = User::create([
            'name' => $request->string('name')->toString(),
            'business_name' => $request->input('business_name'),
            'email' => $request->string('email')->toString(),
            'password' => $request->string('password')->toString(),
        ]);

        $mailFailed = false;
        try {
            event(new Registered($user));
        } catch (Throwable $e) {
            // The account exists; the person can ask for another verification email.
            Log::warning('Verification email could not be sent after registration.', ['user_id' => $user->id, 'error' => $e::class]);
            $mailFailed = true;
        }

        Auth::login($user);
        $request->session()->regenerate();

        return redirect()->route('verification.notice')->with(
            'status',
            $mailFailed ? 'Your account was created, but we could not send the verification email. Please request another one.' : 'Your account was created. Check your email to verify your address.',
        );
    }
}
