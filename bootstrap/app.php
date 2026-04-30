<?php

use App\Http\Middleware\SynchronizeFrontendUrl;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Session\TokenMismatchException;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withCommands([
        __DIR__.'/../app/Console/Commands',
    ])
    ->withMiddleware(function (Middleware $middleware) {
        $middleware->prependToGroup('web', [SynchronizeFrontendUrl::class]);
    })
    ->withExceptions(function (Exceptions $exceptions) {
        $exceptions->reportable(function (TokenMismatchException $e) {
            // #region agent log
            $req = request();
            file_put_contents(
                base_path('debug-c4fe64.log'),
                json_encode([
                    'sessionId' => 'c4fe64',
                    'timestamp' => (int) round(microtime(true) * 1000),
                    'location' => 'bootstrap/app.php',
                    'message' => 'TokenMismatchException (CSRF)',
                    'hypothesisId' => 'H_csrf_session',
                    'data' => [
                        'fullUrl' => $req?->fullUrl(),
                        'hostHeader' => $req?->getHost(),
                        'config_app_url' => config('app.url'),
                    ],
                ])."\n",
                FILE_APPEND | LOCK_EX
            );
            // #endregion
        });
    })->create();
