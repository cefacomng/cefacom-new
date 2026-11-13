<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Auth\Events\Registered;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Auth\Notifications\VerifyEmail;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Notification;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class RegistrationTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutVite();
    }

    private function payload(array $overrides = []): array
    {
        return array_merge([
            'name' => 'Ada Okafor',
            'business_name' => 'Ada Bakes',
            'email' => 'ada@example.com',
            'password' => 'bread-and-jam-77',
            'password_confirmation' => 'bread-and-jam-77',
        ], $overrides);
    }

    public function test_registration_page_renders(): void
    {
        $this->get('/register')->assertOk()->assertInertia(fn (Assert $page) => $page->component('auth/Register'));
    }

    public function test_registering_creates_a_verified_less_customer_with_a_hashed_password_and_signs_in(): void
    {
        Notification::fake();

        $this->post('/register', $this->payload())->assertRedirect(route('verification.notice'));

        $user = User::where('email', 'ada@example.com')->firstOrFail();
        $this->assertSame('Ada Okafor', $user->name);
        $this->assertSame('Ada Bakes', $user->business_name);
        $this->assertNull($user->email_verified_at);
        $this->assertTrue(Hash::check('bread-and-jam-77', $user->password));
        $this->assertNotSame('bread-and-jam-77', $user->password);
        $this->assertTrue($user->isCustomer());
        $this->assertSame(0, $user->roles()->count());
        $this->assertAuthenticatedAs($user);
        Notification::assertSentTo($user, VerifyEmail::class);
    }

    public function test_email_is_trimmed_and_lowercased(): void
    {
        Notification::fake();
        $this->post('/register', $this->payload(['email' => '  Ada@Example.COM ']));

        $this->assertDatabaseHas('users', ['email' => 'ada@example.com']);
    }

    public function test_business_name_is_optional(): void
    {
        Notification::fake();
        $this->post('/register', $this->payload(['business_name' => '']))->assertSessionHasNoErrors();

        $this->assertNull(User::firstWhere('email', 'ada@example.com')->business_name);
    }

    public function test_duplicate_email_is_rejected_regardless_of_case(): void
    {
        User::factory()->create(['email' => 'ada@example.com']);

        $this->post('/register', $this->payload(['email' => 'ADA@example.com']))->assertSessionHasErrors('email');
        $this->assertSame(1, User::count());
    }

    public function test_invalid_input_is_rejected_with_field_errors(): void
    {
        $this->post('/register', [])->assertSessionHasErrors(['name', 'email', 'password']);
        $this->post('/register', $this->payload(['email' => 'not-an-email']))->assertSessionHasErrors('email');
        $this->post('/register', $this->payload(['password_confirmation' => 'different-77']))->assertSessionHasErrors('password');
        $this->assertSame(0, User::count());
    }

    public function test_weak_passwords_are_rejected(): void
    {
        foreach (['short1', 'longbutnonumbers', '1234567890123', str_repeat('a1', 40)] as $password) {
            $this->post('/register', $this->payload(['password' => $password, 'password_confirmation' => $password]))
                ->assertSessionHasErrors('password');
        }
        $this->assertSame(0, User::count());
    }

    public function test_public_registration_can_never_create_staff_or_set_privileged_fields(): void
    {
        Notification::fake();

        $this->post('/register', $this->payload([
            'roles' => ['super_admin'],
            'role' => 'super_admin',
            'is_staff' => true,
            'is_admin' => 1,
            'email_verified_at' => now()->toDateTimeString(),
            'permissions' => ['staff.manage'],
        ]));

        $user = User::firstWhere('email', 'ada@example.com');
        $this->assertTrue($user->isCustomer());
        $this->assertFalse($user->isSuperAdmin());
        $this->assertSame(0, $user->roles()->count());
        $this->assertNull($user->email_verified_at);
    }

    public function test_a_failure_to_send_the_verification_email_does_not_lose_the_account(): void
    {
        Event::listen(Registered::class, fn () => throw new \RuntimeException('mail transport down'));

        $this->post('/register', $this->payload())
            ->assertRedirect(route('verification.notice'))
            ->assertSessionHas('status', fn ($status) => str_contains($status, 'could not send'));

        $this->assertDatabaseHas('users', ['email' => 'ada@example.com']);
    }

    public function test_signed_in_people_are_redirected_away_from_the_registration_page(): void
    {
        $this->actingAs(User::factory()->create())->get('/register')->assertRedirect('/app');
        $this->actingAs(User::factory()->superAdmin()->create())->get('/register')->assertRedirect('/admin');
    }

    public function test_registration_is_rate_limited_per_address(): void
    {
        Notification::fake();
        for ($i = 0; $i < 6; $i++) {
            $this->post('/register', $this->payload(['email' => "user{$i}@example.com"]));
            auth()->logout();
        }

        $this->post('/register', $this->payload(['email' => 'one-too-many@example.com']))->assertStatus(429);
        $this->assertDatabaseMissing('users', ['email' => 'one-too-many@example.com']);
    }
}
