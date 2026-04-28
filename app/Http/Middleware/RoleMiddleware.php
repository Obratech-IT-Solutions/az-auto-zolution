<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class RoleMiddleware
{
    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request            $request
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     * @param  string                              $role
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function handle(Request $request, Closure $next, string $role): Response
    {
        $user = $request->user();
        $userRole = $user ? (($user->role ?? '') === '' ? null : $user->role) : null;

        if (! $user || $userRole !== $role) {
            if ($request->expectsJson()) {
                abort(403, 'Unauthorized.');
            }

            if (! $user) {
                return redirect()->guest(route('login'));
            }

            $home = ($userRole === 'admin') ? 'admin.home' : 'cashier.home';

            return redirect()->route($home)->with(
                'error',
                __('You do not have access to that page.')
            );
        }

        return $next($request);
    }
}
