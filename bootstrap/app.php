<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__ . '/../routes/web.php',
        api: __DIR__ . '/../routes/api.php',
        commands: __DIR__ . '/../routes/console.php',
        health: '/up',
    )
    ->withCommands([
        \App\Console\Commands\MakeFullModuleCommand::class,
    ])
    ->withMiddleware(function (Middleware $middleware) {
        $middleware->api(prepend: [
            \Laravel\Sanctum\Http\Middleware\EnsureFrontendRequestsAreStateful::class,
        ]);

        // ✅ todos os aliases aqui juntos
        $middleware->alias([
      'verified' => \App\Http\Middleware\EnsureEmailIsVerified::class,
    'can' => \App\Http\Middleware\Can::class,
    'track.activity' => \App\Http\Middleware\TrackUserActivity::class,
    'auto.logout' => \App\Http\Middleware\AutoLogoutInactiveUser::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions) {
        //
    })
    ->create();
