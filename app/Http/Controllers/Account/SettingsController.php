<?php

namespace App\Http\Controllers\Account;

use App\Http\Controllers\Controller;
use App\Http\Requests\Account\UpdateProfileRequest;
use App\Models\User;
use App\Notifications\EmailChangeRequested;
use App\Notifications\VerifyNewEmail;
use App\Services\Access\SessionRevoker;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Notification;
use Illuminate\Validation\Rules\Password;
use Inertia\Inertia;
use Inertia\Response;

/**
 * Account settings for the signed-in person only. Every action operates on $request->user();
 * no user id is ever read from the request, so one account cannot be changed through another.
 * Mounted under /app/settings for customers and /admin/account for staff.
 */
class SettingsController extends Controller
{
    public function show(Request $request): Response
    {
        /** @var User $user */
        $user = $request->user();
        $staff = $user->isStaff();

        return Inertia::render('account/Settings', [
            'area' => $staff ? 'admin' : 'customer',
            'basePath' => $staff ? '/admin/account' : '/app/settings',
            'profile' => [
                'name' => $user->name,
                'businessName' => $user->business_name,
                'phone' => $user->phone,
                'email' => $user->email,
                'pendingEmail' => $user->pending_email,
                'emailVerified' => $user->hasVerifiedEmail(),
            ],
            'preferences' => $user->emailPreferenceValues(),
        ]);
    }

    public function updateProfile(UpdateProfileRequest $request): RedirectResponse
    {
        $request->user()->update($request->profileData());

        return back()->with('status', 'Your profile was saved.');
    }

    public function requestEmailChange(Request $request): RedirectResponse
    {
        /** @var User $user */
        $user = $request->user();

        $request->merge(['email' => mb_strtolower(trim($request->string('email')->toString()))]);
        $data = $request->validate([
            'email' => ['required', 'string', 'email:rfc', 'max:254', 'unique:users,email'],
            'current_password' => ['required', 'string', 'current_password'],
        ], [
            'email.unique' => 'That email address cannot be used.',
        ]);

        if ($data['email'] === mb_strtolower($user->email)) {
            return back()->withErrors(['email' => 'That is already your email address.']);
        }

        $user->forceFill(['pending_email' => $data['email']])->save();
        $this->sendEmailChangeNotices($user);

        return back()->with('status', 'We sent a confirmation link to the new address. Your email stays the same until you confirm it.');
    }

    public function resendEmailChange(Request $request): RedirectResponse
    {
        /** @var User $user */
        $user = $request->user();

        if ($user->pending_email === null) {
            return back();
        }

        Notification::route('mail', $user->pending_email)->notify(new VerifyNewEmail($user->id, $user->pending_email));

        return back()->with('status', 'We sent the confirmation link again.');
    }

    public function cancelEmailChange(Request $request): RedirectResponse
    {
        $request->user()->forceFill(['pending_email' => null])->save();

        return back()->with('status', 'The email change was cancelled.');
    }

    public function updatePreferences(Request $request): RedirectResponse
    {
        $keys = array_keys(User::DEFAULT_EMAIL_PREFERENCES);
        $data = $request->validate(array_combine($keys, array_fill(0, count($keys), ['required', 'boolean'])));

        $request->user()->update(['email_preferences' => array_map('boolval', array_intersect_key($data, array_flip($keys)))]);

        return back()->with('status', 'Your email preferences were saved.');
    }

    public function updatePassword(Request $request, SessionRevoker $sessions): RedirectResponse
    {
        $data = $request->validate([
            'current_password' => ['required', 'string', 'current_password'],
            'password' => ['required', 'string', 'confirmed', 'different:current_password', Password::defaults()],
        ]);

        /** @var User $user */
        $user = $request->user();
        $user->update(['password' => $data['password']]);
        // Other devices are signed out; this one stays signed in.
        $sessions->revokeOthers($user, $request->session()->getId());

        return back()->with('status', 'Your password was changed. Other devices were signed out.');
    }

    public function signOutOthers(Request $request, SessionRevoker $sessions): RedirectResponse
    {
        $request->validate(['current_password' => ['required', 'string', 'current_password']]);

        $sessions->revokeOthers($request->user(), $request->session()->getId());

        return back()->with('status', 'All other devices were signed out.');
    }

    private function sendEmailChangeNotices(User $user): void
    {
        Notification::route('mail', $user->pending_email)->notify(new VerifyNewEmail($user->id, $user->pending_email));
        $user->notify(new EmailChangeRequested($user->pending_email));
    }
}
