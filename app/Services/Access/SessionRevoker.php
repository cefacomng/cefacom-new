<?php

namespace App\Services\Access;

use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

/** Ends sign-ins stored in the database session table and invalidates remember-me cookies. */
class SessionRevoker
{
    public function revokeAll(User $user): void
    {
        DB::table('sessions')->where('user_id', $user->getKey())->delete();
        $this->rotateRememberToken($user);
    }

    public function revokeOthers(User $user, string $currentSessionId): void
    {
        DB::table('sessions')->where('user_id', $user->getKey())->where('id', '!=', $currentSessionId)->delete();
        $this->rotateRememberToken($user);
    }

    private function rotateRememberToken(User $user): void
    {
        $user->forceFill(['remember_token' => Str::random(60)])->saveQuietly();
    }
}
