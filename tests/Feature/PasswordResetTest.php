<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Auth\Notifications\ResetPassword;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Password;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class PasswordResetTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutVite();
    }

    public function test_forgot_password_page_renders(): void
    {
        $this->get('/forgot-password')->assertOk()->assertInertia(fn (Assert $page) => $page->component('auth/ForgotPassword'));
    }

    public function test_a_reset_link_is_sent_to_an_existing_account(): void
    {
        Notification::fake();
        $user = User::factory()->create(['email' => 'ada@example.com']);

        $this->post('/forgot-password', ['email' => 'Ada@example.com'])->assertSessionHas('status');

        Notification::assertSentTo($user, ResetPassword::class);
    }

    public function test_the_response_is_identical_for_unknown_emails_and_nothing_is_sent(): void
    {
        Notification::fake();
        User::factory()->create(['email' => 'ada@example.com']);

        $known = $this->post('/forgot-password', ['email' => 'ada@example.com'])->getSession()->get('status');
        $unknown = $this->post('/forgot-password', ['email' => 'nobody@example.com'])->getSession()->get('status');

        $this->assertSame($known, $unknown);
        Notification::assertSentTimes(ResetPassword::class, 1);
    }

    public function test_the_reset_page_renders_with_the_token(): void
    {
        $this->get('/reset-password/abc123?email=ada@example.com')->assertOk()->assertInertia(fn (Assert $page) => $page
            ->component('auth/ResetPassword')->where('token', 'abc123')->where('email', 'ada@example.com'));
    }

    public function test_a_valid_token_resets_the_password_and_signs_out_every_device(): void
    {
        $user = User::factory()->create(['email' => 'ada@example.com']);
        DB::table('sessions')->insert([
            ['id' => 'old-session-a', 'user_id' => $user->id, 'ip_address' => '1.1.1.1', 'user_agent' => 'x', 'payload' => '', 'last_activity' => time()],
            ['id' => 'old-session-b', 'user_id' => $user->id, 'ip_address' => '2.2.2.2', 'user_agent' => 'y', 'payload' => '', 'last_activity' => time()],
        ]);
        $oldRemember = $user->remember_token;
        $token = Password::createToken($user);

        $this->post('/reset-password', [
            'token' => $token, 'email' => 'ada@example.com', 'password' => 'brand-new-pass-5', 'password_confirmation' => 'brand-new-pass-5',
        ])->assertRedirect('/login')->assertSessionHas('status');

        $user->refresh();
        $this->assertTrue(Hash::check('brand-new-pass-5', $user->password));
        $this->assertNotSame($oldRemember, $user->remember_token);
        $this->assertSame(0, DB::table('sessions')->where('user_id', $user->id)->count());
        $this->assertGuest();
    }

    public function test_a_token_cannot_be_used_twice(): void
    {
        $user = User::factory()->create(['email' => 'ada@example.com']);
        $token = Password::createToken($user);
        $body = ['token' => $token, 'email' => 'ada@example.com', 'password' => 'brand-new-pass-5', 'password_confirmation' => 'brand-new-pass-5'];

        $this->post('/reset-password', $body)->assertRedirect('/login');
        $this->post('/reset-password', [...$body, 'password' => 'second-attempt-6', 'password_confirmation' => 'second-attempt-6'])->assertSessionHasErrors('email');

        $this->assertTrue(Hash::check('brand-new-pass-5', $user->refresh()->password));
    }

    public function test_an_invalid_token_or_a_different_email_is_rejected(): void
    {
        $user = User::factory()->create(['email' => 'ada@example.com']);
        User::factory()->create(['email' => 'bola@example.com']);
        $token = Password::createToken($user);

        $this->post('/reset-password', ['token' => 'wrong', 'email' => 'ada@example.com', 'password' => 'brand-new-pass-5', 'password_confirmation' => 'brand-new-pass-5'])->assertSessionHasErrors('email');
        $this->post('/reset-password', ['token' => $token, 'email' => 'bola@example.com', 'password' => 'brand-new-pass-5', 'password_confirmation' => 'brand-new-pass-5'])->assertSessionHasErrors('email');

        $this->assertTrue(Hash::check('correct-horse-9', $user->refresh()->password));
    }

    public function test_the_new_password_must_meet_the_password_rules(): void
    {
        $user = User::factory()->create(['email' => 'ada@example.com']);
        $token = Password::createToken($user);

        $this->post('/reset-password', ['token' => $token, 'email' => 'ada@example.com', 'password' => 'weak', 'password_confirmation' => 'weak'])->assertSessionHasErrors('password');
        $this->assertTrue(Hash::check('correct-horse-9', $user->refresh()->password));
    }

    public function test_reset_requests_are_rate_limited(): void
    {
        Notification::fake();
        for ($i = 0; $i < 5; $i++) {
            $this->post('/forgot-password', ['email' => "user{$i}@example.com"]);
        }

        $this->post('/forgot-password', ['email' => 'another@example.com'])->assertStatus(429);
    }
}
