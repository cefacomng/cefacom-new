<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Services\Access\SessionRevoker;
use Illuminate\Auth\Events\PasswordReset;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;
use Inertia\Response;

class PasswordResetController extends Controller
{
    public function request(): Response
    {
        return Inertia::render('auth/ForgotPassword');
    }

    /** Always answers the same way, so the form cannot be used to discover which emails have accounts. */
    public function email(Request $request): RedirectResponse
    {
        $request->validate(['email' => ['required', 'string', 'email']]);

        Password::sendResetLink(['email' => mb_strtolower(trim($request->string('email')->toString()))]);

        return back()->with('status', 'If that email address has an account, we have sent a link to reset the password.');
    }

    public function reset(Request $request, string $token): Response
    {
        return Inertia::render('auth/ResetPassword', [
            'token' => $token,
            'email' => (string) $request->query('email', ''),
        ]);
    }

    public function store(Request $request, SessionRevoker $sessions): RedirectResponse
    {
        $request->validate([
            'token' => ['required', 'string'],
            'email' => ['required', 'string', 'email'],
            'password' => ['required', 'string', 'confirmed', \Illuminate\Validation\Rules\Password::defaults()],
        ]);

        $status = Password::reset(
            ['email' => mb_strtolower(trim($request->string('email')->toString())), 'password' => $request->string('password')->toString(), 'password_confirmation' => $request->string('password_confirmation')->toString(), 'token' => $request->string('token')->toString()],
            function (User $user, string $password) use ($sessions) {
                $user->forceFill(['password' => $password, 'remember_token' => Str::random(60)])->save();
                // Anyone signed in with the old password, on any device, is signed out.
                $sessions->revokeAll($user);
                event(new PasswordReset($user));
            },
        );

        if ($status !== Password::PASSWORD_RESET) {
            throw ValidationException::withMessages(['email' => 'This password reset link is invalid or has expired. Please request a new one.']);
        }

        return redirect()->route('login')->with('status', 'Your password has been reset. Sign in with your new password.');
    }
}
