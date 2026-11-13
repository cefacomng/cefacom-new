<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Auth\Notifications\VerifyEmail;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\URL;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class EmailVerificationTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutVite();
    }

    private function link(User $user, ?string $hash = null): string
    {
        return URL::temporarySignedRoute('verification.verify', now()->addMinutes(60), ['id' => $user->id, 'hash' => $hash ?? sha1($user->getEmailForVerification())]);
    }

    public function test_the_notice_page_renders_for_an_unverified_customer(): void
    {
        $user = User::factory()->unverified()->create();

        $this->actingAs($user)->get('/email/verify')->assertOk()->assertInertia(fn (Assert $page) => $page
            ->component('auth/VerifyEmail')->where('email', $user->email));
    }

    public function test_a_verified_person_is_sent_home_from_the_notice_page(): void
    {
        $this->actingAs(User::factory()->create())->get('/email/verify')->assertRedirect('/app');
    }

    public function test_unverified_customers_can_see_their_overview_and_manage_their_account_but_not_messaging_areas(): void
    {
        $user = User::factory()->unverified()->create();
        $this->actingAs($user);

        $this->get('/app')->assertOk();
        $this->get('/app/settings')->assertOk();

        foreach (['sms', 'email', 'contacts', 'reports', 'wallet'] as $section) {
            $this->get("/app/{$section}")->assertRedirect(route('verification.notice'));
        }
    }

    public function test_verified_customers_can_open_every_section(): void
    {
        $this->actingAs(User::factory()->create());

        foreach (['sms', 'email', 'contacts', 'reports', 'wallet'] as $section) {
            $this->get("/app/{$section}")->assertOk();
        }
    }

    public function test_the_signed_link_verifies_the_address_and_redirects_home(): void
    {
        $user = User::factory()->unverified()->create();

        $this->actingAs($user)->get($this->link($user))->assertRedirect('/app');

        $this->assertTrue($user->refresh()->hasVerifiedEmail());
    }

    public function test_a_link_with_the_wrong_hash_does_not_verify(): void
    {
        $user = User::factory()->unverified()->create();

        $this->actingAs($user)->get($this->link($user, sha1('someone-else@example.com')))->assertForbidden();

        $this->assertFalse($user->refresh()->hasVerifiedEmail());
    }

    public function test_an_unsigned_or_tampered_link_is_rejected(): void
    {
        $user = User::factory()->unverified()->create();
        $this->actingAs($user);

        $this->get("/email/verify/{$user->id}/".sha1($user->email))->assertForbidden();
        $this->get($this->link($user).'x')->assertForbidden();
        $this->assertFalse($user->refresh()->hasVerifiedEmail());
    }

    public function test_an_expired_link_is_rejected(): void
    {
        $user = User::factory()->unverified()->create();
        $link = URL::temporarySignedRoute('verification.verify', now()->subMinute(), ['id' => $user->id, 'hash' => sha1($user->email)]);

        $this->actingAs($user)->get($link)->assertForbidden();
        $this->assertFalse($user->refresh()->hasVerifiedEmail());
    }

    public function test_someone_elses_link_cannot_verify_my_account_or_theirs(): void
    {
        $victim = User::factory()->unverified()->create();
        $attacker = User::factory()->unverified()->create();

        $this->actingAs($attacker)->get($this->link($victim))->assertForbidden();

        $this->assertFalse($victim->refresh()->hasVerifiedEmail());
        $this->assertFalse($attacker->refresh()->hasVerifiedEmail());
    }

    public function test_a_guest_opening_the_link_must_sign_in_first(): void
    {
        $user = User::factory()->unverified()->create();

        $this->get($this->link($user))->assertRedirect('/login');
        $this->assertFalse($user->refresh()->hasVerifiedEmail());
    }

    public function test_the_verification_email_can_be_resent_and_is_rate_limited(): void
    {
        Notification::fake();
        $user = User::factory()->unverified()->create();
        $this->actingAs($user);

        for ($i = 0; $i < 6; $i++) {
            $this->post('/email/verification-notification')->assertSessionHas('status');
        }
        Notification::assertSentToTimes($user, VerifyEmail::class, 6);

        $this->post('/email/verification-notification')->assertStatus(429);
    }

    public function test_resending_for_an_already_verified_account_sends_nothing(): void
    {
        Notification::fake();
        $user = User::factory()->create();

        $this->actingAs($user)->post('/email/verification-notification')->assertRedirect('/app');

        Notification::assertNothingSent();
    }
}
