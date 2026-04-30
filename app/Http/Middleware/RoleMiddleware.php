<?php

namespace App\Http\Middleware;

use App\Models\User;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class RoleMiddleware
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next, string $role): Response
    {
        $user = $request->user();
        $required = strtolower(trim($role));

        if (! $user) {
            if ($request->expectsJson()) {
                abort(403, 'Unauthorized.');
            }

            return redirect()->guest(route('login'));
        }

        if (! $user->hasValidStaffRole()) {
            // #region agent log
            file_put_contents(
                base_path('debug-c4fe64.log'),
                json_encode([
                    'sessionId' => 'c4fe64',
                    'timestamp' => (int) round(microtime(true) * 1000),
                    'location' => 'RoleMiddleware',
                    'message' => 'invalid_role_logout',
                    'hypothesisId' => 'H_invalid_db_role',
                    'data' => [
                        'path' => $request->path(),
                        'normalizedRole' => $user->normalizedRole(),
                    ],
                ])."\n",
                FILE_APPEND | LOCK_EX
            );
            // #endregion

            Auth::logout();
            $request->session()->invalidate();
            $request->session()->regenerateToken();

            return redirect()->route('login')->with(
                'error',
                __('Your account has no valid role. Please sign in again after an administrator updates it.')
            );
        }

        $normalized = $user->normalizedRole();

        if ($normalized !== $required) {
            // #region agent log
            file_put_contents(
                base_path('debug-c4fe64.log'),
                json_encode([
                    'sessionId' => 'c4fe64',
                    'timestamp' => (int) round(microtime(true) * 1000),
                    'location' => 'RoleMiddleware',
                    'message' => 'role_mismatch_redirect',
                    'hypothesisId' => 'H_rbac_wrong_area',
                    'data' => [
                        'path' => $request->path(),
                        'normalizedRole' => $normalized,
                        'required' => $required,
                    ],
                ])."\n",
                FILE_APPEND | LOCK_EX
            );
            // #endregion

            if ($request->expectsJson()) {
                abort(403, 'Unauthorized.');
            }

            $home = $normalized === User::ROLE_ADMIN ? 'admin.home' : 'cashier.home';

            return redirect()->route($home)->with(
                'error',
                __('You do not have access to that page.')
            );
        }

        return $next($request);
    }
}
