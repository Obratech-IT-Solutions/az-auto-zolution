<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\URL;
use Symfony\Component\HttpFoundation\Response;

/**
 * Keeps signed/absolute URLs in sync with the host the browser used.
 * Fixes CSRF/session 419 when APP_URL differs (e.g. Valet hostname vs artisan serve IP).
 */
class SynchronizeFrontendUrl
{
    public function handle(Request $request, Closure $next): Response
    {
        if (app()->environment('local')) {
            $configured = config('app.url');
            $forcedRoot = $request->getScheme().'://'.$request->getHttpHost();

            URL::forceRootUrl($forcedRoot);

            // #region agent log
            if ($request->is('login') && $request->isMethod('GET')) {
                file_put_contents(
                    base_path('debug-c4fe64.log'),
                    json_encode([
                        'sessionId' => 'c4fe64',
                        'timestamp' => (int) round(microtime(true) * 1000),
                        'location' => 'SynchronizeFrontendUrl',
                        'message' => 'local URL sync',
                        'hypothesisId' => 'H_host',
                        'data' => [
                            'config_app_url' => $configured,
                            'forced_root' => $forcedRoot,
                            'login_route_abs' => route('login'),
                        ],
                    ])."\n",
                    FILE_APPEND | LOCK_EX
                );
            }
            // #endregion
        }

        return $next($request);
    }
}
