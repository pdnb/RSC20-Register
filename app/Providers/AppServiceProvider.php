<?php

namespace App\Providers;

use App\Auth\Auth0Provider;
use App\Models\RegistrationGroup;
use App\Models\User;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\ServiceProvider;
use Laravel\Socialite\Socialite;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        //
    }

    public function boot(): void
    {
        Socialite::extend('auth0', fn ($app) => Socialite::buildProvider(Auth0Provider::class, $app['config']['services.auth0']));

        RateLimiter::for('admin-auth0', fn (Request $request) => Limit::perMinute(10)->by($request->ip()));

        // Coordinators only see their own university's group; admins see all.
        Gate::define('manage-group', fn (User $user, RegistrationGroup $group) => $user->isAdmin()
            || ($user->isCoordinator() && $user->university_id === $group->university_id));
    }
}
