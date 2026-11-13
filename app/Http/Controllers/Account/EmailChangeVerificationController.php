<?php

namespace App\Http\Controllers\Account;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Services\Audit\AuditLogger;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class EmailChangeVerificationController extends Controller
{
    /** Signed link, opened while signed in as the same account. */
    public function __invoke(Request $request, string $id, string $hash, AuditLogger $audit): RedirectResponse
    {
        /** @var User $user */
        $user = $request->user();
        $settings = $user->isStaff() ? '/admin/account' : '/app/settings';

        $valid = (string) $user->getKey() === $id
            && $user->pending_email !== null
            && hash_equals(sha1($user->pending_email), $hash);

        if (! $valid) {
            return redirect($settings)->with('status', 'That confirmation link is no longer valid.');
        }

        if (User::where('email', $user->pending_email)->whereKeyNot($user->getKey())->exists()) {
            $user->forceFill(['pending_email' => null])->save();

            return redirect($settings)->with('status', 'That email address can no longer be used.');
        }

        $old = $user->email;
        $user->forceFill(['email' => $user->pending_email, 'pending_email' => null, 'email_verified_at' => now()])->save();
        $audit->record($user, 'account.email_changed', $user, ['email' => $old], ['email' => $user->email]);

        return redirect($settings)->with('status', 'Your email address was changed.');
    }
}
