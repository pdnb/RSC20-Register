<?php

namespace Tests\Feature;

use App\Livewire\Auth\ChangePassword;
use App\Livewire\Auth\Login;
use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Laravel\Socialite\Socialite;
use Laravel\Socialite\Two\User as SocialiteUser;
use Livewire\Livewire;
use Mockery;
use Tests\TestCase;

class AuthTest extends TestCase
{
    public function test_coordinator_logs_in_with_username(): void
    {
        $user = $this->coordinator(attrs: ['username' => 'rsc20-u37']);

        Livewire::test(Login::class)
            ->set('username', 'RSC20-U37')
            ->set('password', 'Secret123')
            ->call('login')
            ->assertRedirect(route('coordinator.dashboard'));

        $this->assertAuthenticatedAs($user);
    }

    public function test_wrong_password_and_suspended_account_are_rejected(): void
    {
        $this->coordinator(attrs: ['username' => 'active-one']);
        $this->coordinator(attrs: ['username' => 'suspended', 'is_active' => false]);

        Livewire::test(Login::class)->set('username', 'active-one')->set('password', 'nope')->call('login')->assertHasErrors('username');
        Livewire::test(Login::class)->set('username', 'suspended')->set('password', 'Secret123')->call('login')->assertHasErrors('username');

        $this->assertGuest();
    }

    public function test_login_is_rate_limited(): void
    {
        $this->coordinator(attrs: ['username' => 'target']);

        foreach (range(1, 5) as $_) {
            Livewire::test(Login::class)->set('username', 'target')->set('password', 'bad')->call('login');
        }

        Livewire::test(Login::class)->set('username', 'target')->set('password', 'Secret123')->call('login')
            ->assertHasErrors('username');
        $this->assertGuest();
    }

    public function test_first_login_must_change_password(): void
    {
        $user = $this->coordinator(attrs: ['must_change_password' => true]);

        $this->actingAs($user)->get(route('coordinator.dashboard'))->assertRedirect(route('coordinator.password'));

        Livewire::actingAs($user)->test(ChangePassword::class)
            ->set('current_password', 'Secret123')
            ->set('password', 'NewPass456')
            ->set('password_confirmation', 'NewPass456')
            ->call('save')
            ->assertRedirect(route('coordinator.dashboard'));

        $user->refresh();
        $this->assertFalse($user->must_change_password);
        $this->assertTrue(Hash::check('NewPass456', $user->password));
        $this->actingAs($user)->get(route('coordinator.dashboard'))->assertOk();
    }

    public function test_there_is_no_self_registration(): void
    {
        $this->get('/register')->assertNotFound();
    }

    public function test_coordinator_cannot_open_admin_pages(): void
    {
        $this->actingAs($this->coordinator())->get(route('admin.dashboard'))->assertForbidden();

        auth()->logout();
        $this->get(route('admin.dashboard'))->assertRedirect(route('admin.login'));
        $this->get(route('coordinator.dashboard'))->assertRedirect(route('login'));
    }

    private function fakeAuth0(string $sub, string $email, bool $verified = true): void
    {
        config(['services.auth0.domain' => 'tenant.example.auth0.com', 'services.auth0.client_id' => 'id', 'services.auth0.client_secret' => 'secret']);

        $oauth = (new SocialiteUser)->setRaw(['sub' => $sub, 'email' => $email, 'email_verified' => $verified])
            ->map(['id' => $sub, 'email' => $email, 'name' => 'Auth0 Person']);

        $provider = Mockery::mock();
        $provider->shouldReceive('user')->andReturn($oauth);
        Socialite::shouldReceive('driver')->with('auth0')->andReturn($provider);
    }

    public function test_auth0_allowlisted_admin_signs_in(): void
    {
        $admin = $this->admin();
        $this->fakeAuth0('auth0|abc', strtoupper($admin->email));

        $this->get(route('admin.auth0.callback'))->assertRedirect(route('admin.dashboard'));

        $this->assertAuthenticatedAs($admin);
        $this->assertSame('auth0|abc', $admin->fresh()->auth0_sub);
    }

    public function test_auth0_staff_lands_on_checkin(): void
    {
        $staff = $this->staff();
        $this->fakeAuth0('auth0|staff', $staff->email);

        $this->get(route('admin.auth0.callback'))->assertRedirect(route('staff.checkin'));
        $this->actingAs($staff->fresh())->get(route('admin.dashboard'))->assertForbidden();
    }

    public function test_auth0_rejects_unknown_and_unverified_emails(): void
    {
        $this->fakeAuth0('auth0|x', 'stranger@example.test');
        $this->get(route('admin.auth0.callback'))->assertRedirect(route('admin.login'))->assertSessionHasErrors('auth0');
        $this->assertGuest();

        $admin = $this->admin();
        $this->fakeAuth0('auth0|y', $admin->email, verified: false);
        $this->get(route('admin.auth0.callback'))->assertSessionHasErrors('auth0');
        $this->assertGuest();
    }

    public function test_auth0_rejects_coordinator_email_and_different_sub(): void
    {
        $this->coordinator(attrs: ['email' => 'coord@example.test']);
        $this->fakeAuth0('auth0|c', 'coord@example.test');
        $this->get(route('admin.auth0.callback'))->assertSessionHasErrors('auth0');

        $admin = $this->admin();
        $admin->forceFill(['auth0_sub' => 'auth0|original'])->save();
        $this->fakeAuth0('auth0|imposter', $admin->email);
        $this->get(route('admin.auth0.callback'))->assertSessionHasErrors('auth0');
        $this->assertGuest();
    }

    public function test_allow_admin_command(): void
    {
        $this->artisan('admin:allow', ['email' => 'Boss@SRU.ac.th'])->assertSuccessful();
        $this->assertTrue(User::query()->where('email', 'boss@sru.ac.th')->first()->isAdmin());
    }
}
