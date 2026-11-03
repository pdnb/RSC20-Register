<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/** role:admin | role:staff (admin or staff) | role:coordinator */
class EnsureRole
{
    public function handle(Request $request, Closure $next, string $role): Response
    {
        $user = $request->user();

        $allowed = $user && $user->is_active && match ($role) {
            'admin' => $user->isAdmin(),
            'staff' => $user->isStaff(),
            'coordinator' => $user->isCoordinator(),
            default => false,
        };

        abort_unless($allowed, 403);

        if ($role === 'coordinator' && $user->must_change_password && ! $request->routeIs('coordinator.password')) {
            return redirect()->route('coordinator.password');
        }

        return $next($request);
    }
}
