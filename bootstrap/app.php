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
        // 1. Configuração de Proxy para Docker
        $middleware->trustProxies(at: '*');

        // 2. Sanctum Stateful
        $middleware->api(prepend: [
            \Laravel\Sanctum\Http\Middleware\EnsureFrontendRequestsAreStateful::class,
        ]);

        // 3. Exceção temporária de CSRF para validar o ambiente
        $middleware->validateCsrfTokens(except: [
            'api/auth/login',
            'api/*',
        ]);

        // Aliases originais do seu projeto
        $middleware->alias([
            'verified' => \App\Http\Middleware\EnsureEmailIsVerified::class,
            'can' => \App\Http\Middleware\Can::class,
            'track.activity' => \App\Http\Middleware\TrackUserActivity::class,
            'auto.logout' => \App\Http\Middleware\AutoLogoutInactiveUser::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions) {
        //
    })->create();
