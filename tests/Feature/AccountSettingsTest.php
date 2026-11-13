<?php

namespace Tests\Feature;

use App\Models\AuditLog;
use App\Models\User;
use App\Notifications\EmailChangeRequested;
use App\Notifications\VerifyNewEmail;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\URL;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class AccountSettingsTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutVite();
    }

    public function test_settings_page_shows_only_the_signed_in_persons_data(): void
    {
        $other = User::factory()->create(['name' => 'Other Person']);
        $user = User::factory()->create(['name' => 'Ada Okafor', 'business_name' => 'Ada Bakes', 'phone' => '+2348031234567']);

        $this->actingAs($user)->get('/app/settings')->assertOk()->assertInertia(fn (Assert $page) => $page
            ->component('account/Settings')
            ->where('area', 'customer')->where('basePath', '/app/settings')
            ->where('profile.name', 'Ada Okafor')->where('profile.businessName', 'Ada Bakes')
            ->where('profile.phone', '+2348031234567')->where('profile.email', $user->email)
            ->where('preferences', ['low_balance_alerts' => true, 'campaign_reports' => true, 'product_updates' => false]));
        $this->assertNotSame($other->email, $user->email);
    }

    public function test_staff_use_their_own_account_page_inside_the_admin_area(): void
    {
        $staff = User::factory()->superAdmin()->create();

        $this->actingAs($staff)->get('/admin/account')->assertOk()->assertInertia(fn (Assert $page) => $page
            ->where('area', 'admin')->where('basePath', '/admin/account'));
        $this->actingAs($staff)->get('/app/settings')->assertRedirect('/admin');
    }

    public function test_profile_can_be_updated_and_the_phone_is_stored_in_canonical_form(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)->patch('/app/settings/profile', ['name' => '  Ada N. Okafor ', 'business_name' => 'Ada Bakes Ltd', 'phone' => '0803 123 4567'])
            ->assertSessionHasNoErrors()->assertSessionHas('status');

        $user->refresh();
        $this->assertSame('Ada N. Okafor', $user->name);
        $this->assertSame('Ada Bakes Ltd', $user->business_name);
        $this->assertSame('+2348031234567', $user->phone);
    }

    public function test_phone_and_business_name_can_be_cleared(): void
    {
        $user = User::factory()->create(['phone' => '+2348031234567', 'business_name' => 'X']);

        $this->actingAs($user)->patch('/app/settings/profile', ['name' => $user->name, 'business_name' => '', 'phone' => ''])->assertSessionHasNoErrors();

        $this->assertNull($user->refresh()->phone);
        $this->assertNull($user->business_name);
    }

    public function test_invalid_profile_input_is_rejected(): void
    {
        $user = User::factory()->create(['name' => 'Keep Me']);

        $this->actingAs($user)->patch('/app/settings/profile', ['name' => '', 'phone' => '12345'])->assertSessionHasErrors(['name', 'phone']);
        $this->actingAs($user)->patch('/app/settings/profile', ['name' => str_repeat('a', 121)])->assertSessionHasErrors('name');
        $this->assertSame('Keep Me', $user->refresh()->name);
    }

    public function test_profile_update_cannot_touch_another_account_or_privileged_fields(): void
    {
        $victim = User::factory()->create(['name' => 'Victim', 'email' => 'victim@example.com']);
        $user = User::factory()->create(['email' => 'me@example.com']);

        $this->actingAs($user)->patch('/app/settings/profile', [
            'name' => 'Mine', 'id' => $victim->id, 'user_id' => $victim->id, 'email' => 'hijack@example.com',
            'email_verified_at' => null, 'password' => 'injected-pass-1', 'roles' => ['super_admin'],
        ]);

        $this->assertSame('Victim', $victim->refresh()->name);
        $this->assertSame('victim@example.com', $victim->email);
        $user->refresh();
        $this->assertSame('Mine', $user->name);
        $this->assertSame('me@example.com', $user->email);
        $this->assertNotNull($user->email_verified_at);
        $this->assertTrue(Hash::check('correct-horse-9', $user->password));
        $this->assertTrue($user->isCustomer());
    }

    public function test_settings_require_sign_in(): void
    {
        foreach (['patch' => '/app/settings/profile', 'post' => '/app/settings/email', 'patch ' => '/app/settings/preferences', 'put' => '/app/settings/password', 'post ' => '/app/settings/sessions'] as $method => $path) {
            $this->call(strtoupper(trim($method)), $path)->assertRedirect('/login');
        }
    }

    public function test_email_change_needs_the_current_password(): void
    {
        $user = User::factory()->create(['email' => 'old@example.com']);

        $this->actingAs($user)->post('/app/settings/email', ['email' => 'new@example.com', 'current_password' => 'wrong-pass-1'])->assertSessionHasErrors('current_password');

        $this->assertNull($user->refresh()->pending_email);
    }

    public function test_email_change_sends_a_confirmation_to_the_new_address_and_a_warning_to_the_old_one_without_changing_the_email(): void
    {
        Notification::fake();
        $user = User::factory()->create(['email' => 'old@example.com']);

        $this->actingAs($user)->post('/app/settings/email', ['email' => ' New@Example.com ', 'current_password' => 'correct-horse-9'])
            ->assertSessionHasNoErrors()->assertSessionHas('status');

        $user->refresh();
        $this->assertSame('old@example.com', $user->email);
        $this->assertSame('new@example.com', $user->pending_email);
        Notification::assertSentOnDemand(VerifyNewEmail::class, fn ($n, $channels, $notifiable) => $notifiable->routes['mail'] === 'new@example.com');
        Notification::assertSentTo($user, EmailChangeRequested::class);
    }

    public function test_email_change_rejects_taken_and_unchanged_addresses(): void
    {
        Notification::fake();
        User::factory()->create(['email' => 'taken@example.com']);
        $user = User::factory()->create(['email' => 'old@example.com']);

        $this->actingAs($user)->post('/app/settings/email', ['email' => 'taken@example.com', 'current_password' => 'correct-horse-9'])->assertSessionHasErrors('email');
        $this->actingAs($user)->post('/app/settings/email', ['email' => 'OLD@example.com', 'current_password' => 'correct-horse-9'])->assertSessionHasErrors('email');

        $this->assertNull($user->refresh()->pending_email);
        Notification::assertNothingSent();
    }

    public function test_the_confirmation_link_switches_the_email_marks_it_verified_and_is_audited(): void
    {
        $user = User::factory()->create(['email' => 'old@example.com', 'pending_email' => 'new@example.com', 'email_verified_at' => null]);
        $link = URL::temporarySignedRoute('email.change.verify', now()->addMinutes(60), ['id' => $user->id, 'hash' => sha1('new@example.com')]);

        $this->actingAs($user)->get($link)->assertRedirect('/app/settings');

        $user->refresh();
        $this->assertSame('new@example.com', $user->email);
        $this->assertNull($user->pending_email);
        $this->assertTrue($user->hasVerifiedEmail());
        $entry = AuditLog::where('action', 'account.email_changed')->firstOrFail();
        $this->assertSame($user->id, $entry->actor_id);
        $this->assertSame('old@example.com', $entry->before['email']);
        $this->assertSame('new@example.com', $entry->after['email']);
    }

    public function test_a_confirmation_link_cannot_be_used_by_someone_else_or_with_a_wrong_hash_or_unsigned(): void
    {
        $owner = User::factory()->create(['email' => 'old@example.com', 'pending_email' => 'new@example.com']);
        $attacker = User::factory()->create(['pending_email' => 'attacker-new@example.com']);
        $link = URL::temporarySignedRoute('email.change.verify', now()->addMinutes(60), ['id' => $owner->id, 'hash' => sha1('new@example.com')]);

        $this->actingAs($attacker)->get($link)->assertRedirect('/app/settings')->assertSessionHas('status', fn ($s) => str_contains($s, 'no longer valid'));
        $wrongHash = URL::temporarySignedRoute('email.change.verify', now()->addMinutes(60), ['id' => $owner->id, 'hash' => sha1('other@example.com')]);
        $this->actingAs($owner)->get($wrongHash)->assertRedirect('/app/settings');
        $this->actingAs($owner)->get("/email/change/{$owner->id}/".sha1('new@example.com'))->assertForbidden();

        $this->assertSame('old@example.com', $owner->refresh()->email);
        $this->assertSame('attacker-new@example.com', $attacker->refresh()->pending_email);
    }

    public function test_a_confirmation_fails_if_the_address_was_taken_in_the_meantime(): void
    {
        $user = User::factory()->create(['email' => 'old@example.com', 'pending_email' => 'new@example.com']);
        User::factory()->create(['email' => 'new@example.com']);
        $link = URL::temporarySignedRoute('email.change.verify', now()->addMinutes(60), ['id' => $user->id, 'hash' => sha1('new@example.com')]);

        $this->actingAs($user)->get($link)->assertSessionHas('status', fn ($s) => str_contains($s, 'can no longer be used'));

        $this->assertSame('old@example.com', $user->refresh()->email);
        $this->assertNull($user->pending_email);
    }

    public function test_a_pending_email_change_can_be_resent_and_cancelled(): void
    {
        Notification::fake();
        $user = User::factory()->create(['pending_email' => 'new@example.com']);

        $this->actingAs($user)->post('/app/settings/email/resend')->assertSessionHas('status');
        Notification::assertSentOnDemandTimes(VerifyNewEmail::class, 1);

        $this->actingAs($user)->delete('/app/settings/email')->assertSessionHas('status');
        $this->assertNull($user->refresh()->pending_email);
    }

    public function test_email_preferences_are_saved_and_unknown_keys_are_ignored(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)->patch('/app/settings/preferences', [
            'low_balance_alerts' => false, 'campaign_reports' => true, 'product_updates' => true, 'admin_override' => true,
        ])->assertSessionHasNoErrors();

        $this->assertEquals(['low_balance_alerts' => false, 'campaign_reports' => true, 'product_updates' => true], $user->refresh()->email_preferences);
    }

    public function test_email_preferences_require_every_choice_to_be_a_boolean(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)->patch('/app/settings/preferences', ['low_balance_alerts' => 'maybe'])->assertSessionHasErrors(['low_balance_alerts', 'campaign_reports', 'product_updates']);
    }

    public function test_changing_the_password_needs_the_current_one_and_a_different_valid_new_one(): void
    {
        $user = User::factory()->create();
        $this->actingAs($user);

        $this->put('/app/settings/password', ['current_password' => 'wrong-pass-1', 'password' => 'brand-new-pass-5', 'password_confirmation' => 'brand-new-pass-5'])->assertSessionHasErrors('current_password');
        $this->put('/app/settings/password', ['current_password' => 'correct-horse-9', 'password' => 'correct-horse-9', 'password_confirmation' => 'correct-horse-9'])->assertSessionHasErrors('password');
        $this->put('/app/settings/password', ['current_password' => 'correct-horse-9', 'password' => 'weak', 'password_confirmation' => 'weak'])->assertSessionHasErrors('password');
        $this->put('/app/settings/password', ['current_password' => 'correct-horse-9', 'password' => 'brand-new-pass-5', 'password_confirmation' => 'mismatch-5-xx'])->assertSessionHasErrors('password');

        $this->assertTrue(Hash::check('correct-horse-9', $user->refresh()->password));
    }

    public function test_a_password_change_signs_out_other_devices_but_keeps_this_one(): void
    {
        $user = User::factory()->create();
        $this->actingAs($user)->startSession();
        $current = session()->getId();
        $this->withCookie(config('session.cookie'), $current);
        DB::table('sessions')->insert([
            ['id' => $current, 'user_id' => $user->id, 'ip_address' => '1.1.1.1', 'user_agent' => 'this', 'payload' => '', 'last_activity' => time()],
            ['id' => 'phone-session', 'user_id' => $user->id, 'ip_address' => '2.2.2.2', 'user_agent' => 'phone', 'payload' => '', 'last_activity' => time()],
        ]);
        $oldRemember = $user->remember_token;

        $this->put('/app/settings/password', ['current_password' => 'correct-horse-9', 'password' => 'brand-new-pass-5', 'password_confirmation' => 'brand-new-pass-5'])->assertSessionHas('status');

        $user->refresh();
        $this->assertTrue(Hash::check('brand-new-pass-5', $user->password));
        $this->assertNotSame($oldRemember, $user->remember_token);
        $this->assertSame([$current], DB::table('sessions')->where('user_id', $user->id)->pluck('id')->all());
    }

    public function test_signing_out_other_devices_needs_the_password_and_only_affects_my_sessions(): void
    {
        $user = User::factory()->create();
        $other = User::factory()->create();
        $this->actingAs($user)->startSession();
        DB::table('sessions')->insert([
            ['id' => 'my-phone', 'user_id' => $user->id, 'ip_address' => '2.2.2.2', 'user_agent' => 'phone', 'payload' => '', 'last_activity' => time()],
            ['id' => 'their-phone', 'user_id' => $other->id, 'ip_address' => '3.3.3.3', 'user_agent' => 'phone', 'payload' => '', 'last_activity' => time()],
        ]);

        $this->post('/app/settings/sessions', ['current_password' => 'wrong-pass-1'])->assertSessionHasErrors('current_password');
        $this->assertSame(1, DB::table('sessions')->where('id', 'my-phone')->count());

        $this->post('/app/settings/sessions', ['current_password' => 'correct-horse-9'])->assertSessionHas('status');
        $this->assertSame(0, DB::table('sessions')->where('id', 'my-phone')->count());
        $this->assertSame(1, DB::table('sessions')->where('id', 'their-phone')->count());
    }

    public function test_staff_can_use_the_same_actions_under_the_admin_prefix(): void
    {
        $staff = User::factory()->superAdmin()->create();

        $this->actingAs($staff)->patch('/admin/account/profile', ['name' => 'Boss Person', 'phone' => '09012345678'])->assertSessionHasNoErrors();

        $this->assertSame('Boss Person', $staff->refresh()->name);
        $this->assertSame('+2349012345678', $staff->phone);
    }

    public function test_customers_cannot_use_the_staff_account_routes(): void
    {
        $this->actingAs(User::factory()->create())->get('/admin/account')->assertNotFound();
        $this->actingAs(User::factory()->create())->patch('/admin/account/profile', ['name' => 'x'])->assertNotFound();
    }
}
