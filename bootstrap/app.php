<?php

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
        //
    })
    ->withExceptions(function (Exceptions $exceptions) {
        $exceptions->reportable(function (TokenMismatchException $e) {
            // #region agent log
            $path = base_path('.cursor/debug-c4fe64.log');
            file_put_contents(
                $path,
                json_encode([
                    'sessionId' => 'c4fe64',
                    'timestamp' => (int) round(microtime(true) * 1000),
                    'location' => 'bootstrap/app.php',
                    'message' => 'TokenMismatchException (CSRF)',
                    'hypothesisId' => 'H_update_csrf',
                ]) . "\n",
                FILE_APPEND | LOCK_EX
            );
            // #endregion
        });
    })->create();
