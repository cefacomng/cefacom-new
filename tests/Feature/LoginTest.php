<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class LoginTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutVite();
    }

    public function test_login_page_renders(): void
    {
        $this->get('/login')->assertOk()->assertInertia(fn (Assert $page) => $page->component('auth/Login'));
    }

    public function test_a_customer_signs_in_and_lands_in_the_customer_area(): void
    {
        $user = User::factory()->create(['email' => 'ada@example.com']);

        $this->post('/login', ['email' => 'ada@example.com', 'password' => 'correct-horse-9'])->assertRedirect('/app');
        $this->assertAuthenticatedAs($user);
    }

    public function test_staff_land_in_the_admin_area(): void
    {
        User::factory()->superAdmin()->create(['email' => 'boss@example.com']);

        $this->post('/login', ['email' => 'boss@example.com', 'password' => 'correct-horse-9'])->assertRedirect('/admin');
    }

    public function test_email_matching_ignores_case_and_surrounding_spaces(): void
    {
        User::factory()->create(['email' => 'ada@example.com']);

        $this->post('/login', ['email' => '  ADA@Example.com ', 'password' => 'correct-horse-9'])->assertRedirect('/app');
    }

    public function test_wrong_password_and_unknown_email_give_the_same_generic_error(): void
    {
        User::factory()->create(['email' => 'ada@example.com']);

        $wrong = $this->from('/login')->post('/login', ['email' => 'ada@example.com', 'password' => 'not-the-password-1']);
        $wrong->assertSessionHasErrors(['email' => 'These credentials do not match our records.']);
        $this->assertGuest();

        $unknown = $this->from('/login')->post('/login', ['email' => 'nobody@example.com', 'password' => 'not-the-password-1']);
        $unknown->assertSessionHasErrors(['email' => 'These credentials do not match our records.']);
        $this->assertGuest();
    }

    public function test_missing_fields_are_validated(): void
    {
        $this->post('/login', [])->assertSessionHasErrors(['email', 'password']);
    }

    public function test_repeated_failures_lock_the_sign_in_even_for_the_correct_password(): void
    {
        User::factory()->create(['email' => 'ada@example.com']);

        for ($i = 0; $i < 5; $i++) {
            $this->post('/login', ['email' => 'ada@example.com', 'password' => 'wrong-password-1']);
        }

        $response = $this->post('/login', ['email' => 'ada@example.com', 'password' => 'correct-horse-9']);

        $response->assertSessionHasErrors('email');
        $this->assertStringContainsString('Too many sign-in attempts', session('errors')->first('email'));
        $this->assertGuest();
    }

    public function test_a_successful_sign_in_resets_the_failure_count(): void
    {
        User::factory()->create(['email' => 'ada@example.com']);

        for ($i = 0; $i < 4; $i++) {
            $this->post('/login', ['email' => 'ada@example.com', 'password' => 'wrong-password-1']);
        }
        $this->post('/login', ['email' => 'ada@example.com', 'password' => 'correct-horse-9'])->assertRedirect('/app');
        $this->post('/logout');

        for ($i = 0; $i < 4; $i++) {
            $this->post('/login', ['email' => 'ada@example.com', 'password' => 'wrong-password-1']);
        }
        $this->post('/login', ['email' => 'ada@example.com', 'password' => 'correct-horse-9'])->assertRedirect('/app');
    }

    public function test_lockout_is_per_email_so_one_account_cannot_lock_out_another(): void
    {
        User::factory()->create(['email' => 'ada@example.com']);
        User::factory()->create(['email' => 'bola@example.com']);

        for ($i = 0; $i < 6; $i++) {
            $this->post('/login', ['email' => 'ada@example.com', 'password' => 'wrong-password-1']);
        }

        $this->post('/login', ['email' => 'bola@example.com', 'password' => 'correct-horse-9'])->assertRedirect('/app');
    }

    public function test_remember_me_sets_a_remember_cookie(): void
    {
        User::factory()->create(['email' => 'ada@example.com']);

        $response = $this->post('/login', ['email' => 'ada@example.com', 'password' => 'correct-horse-9', 'remember' => true]);

        $this->assertTrue(collect($response->headers->getCookies())->contains(fn ($c) => str_starts_with($c->getName(), 'remember_web_')));
    }

    public function test_the_session_id_changes_on_sign_in(): void
    {
        User::factory()->create(['email' => 'ada@example.com']);
        $this->get('/login');
        $before = session()->getId();

        $this->post('/login', ['email' => 'ada@example.com', 'password' => 'correct-horse-9']);

        $this->assertNotSame($before, session()->getId());
    }

    public function test_the_intended_page_is_honoured_inside_the_right_area(): void
    {
        User::factory()->create(['email' => 'ada@example.com']);

        $this->get('/app/settings')->assertRedirect('/login');
        $this->post('/login', ['email' => 'ada@example.com', 'password' => 'correct-horse-9'])->assertRedirect('/app/settings');
    }

    public function test_an_intended_url_outside_the_users_area_is_ignored(): void
    {
        User::factory()->superAdmin()->create(['email' => 'boss@example.com']);

        $this->withSession(['url.intended' => 'http://localhost/app/sms'])
            ->post('/login', ['email' => 'boss@example.com', 'password' => 'correct-horse-9'])->assertRedirect('/admin');
    }

    public function test_an_intended_url_pointing_to_another_site_is_never_followed(): void
    {
        User::factory()->create(['email' => 'ada@example.com']);

        foreach (['https://evil.example/app', '//evil.example/app', 'https://evil.example/app/x?next=1', 'javascript:alert(1)', 'http://localhost/app/../admin', '/app\\..\\evil'] as $intended) {
            $response = $this->withSession(['url.intended' => $intended])
                ->post('/login', ['email' => 'ada@example.com', 'password' => 'correct-horse-9']);

            $location = $response->headers->get('Location');
            $this->assertStringNotContainsString('evil.example', (string) $location, $intended);
            $this->assertStringNotContainsString('..', (string) $location, $intended);
            $this->assertSame(parse_url(url('/'), PHP_URL_HOST), parse_url((string) $location, PHP_URL_HOST), $intended);
            $this->post('/logout');
        }
    }

    public function test_an_intended_url_keeps_its_query_string_inside_the_right_area(): void
    {
        User::factory()->create(['email' => 'ada@example.com']);

        $this->withSession(['url.intended' => 'http://localhost/app/settings?tab=email'])
            ->post('/login', ['email' => 'ada@example.com', 'password' => 'correct-horse-9'])->assertRedirect('/app/settings?tab=email');
    }

    public function test_logout_ends_the_session(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)->post('/logout')->assertRedirect('/');
        $this->assertGuest();
        $this->get('/app')->assertRedirect('/login');
    }

    public function test_guests_are_sent_to_login_for_protected_areas(): void
    {
        foreach (['/app', '/app/settings', '/app/sms', '/admin', '/admin/customers', '/admin/account', '/email/verify'] as $path) {
            $this->get($path)->assertRedirect('/login');
        }
    }

    public function test_signed_in_people_cannot_reach_the_login_page(): void
    {
        $this->actingAs(User::factory()->create())->get('/login')->assertRedirect('/app');
    }

    public function test_the_login_form_cannot_be_posted_without_a_csrf_token(): void
    {
        // Laravel skips request-forgery checks while the environment is "testing"; switch it off for this test.
        $this->app['env'] = 'production';
        $this->withMiddleware();

        $this->post('/login', ['email' => 'a@example.com', 'password' => 'x'])->assertStatus(419);
    }
}
