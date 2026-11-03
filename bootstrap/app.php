<?php

use App\Http\Middleware\EnsureRole;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Request;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->alias(['role' => EnsureRole::class]);

        // Admins sign in on their own page; everyone else lands on the coordinator login.
        $middleware->redirectGuestsTo(fn (Request $request) => $request->is('admin', 'admin/*', 'staff/*')
            ? route('admin.login')
            : route('login'));

        $middleware->redirectUsersTo(fn (Request $request) => $request->user()?->isCoordinator()
            ? route('coordinator.dashboard')
            : ($request->user()?->isAdmin() ? route('admin.dashboard') : route('staff.checkin')));
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        $exceptions->shouldRenderJsonWhen(
            fn (Request $request) => $request->is('api/*') || $request->expectsJson(),
        );
    })->create();
