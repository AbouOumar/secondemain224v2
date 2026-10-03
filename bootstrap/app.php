<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use App\Http\Middleware\EnsureAccountIsActive;
use App\Http\Middleware\RoleMiddleware;
use Illuminate\Http\Request;
use Illuminate\Http\Exceptions\ThrottleRequestsException;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
        then: function () {
            require __DIR__.'/../routes/admin.php';
        },
    )
    ->withMiddleware(function (Middleware $middleware): void {
        // Désinscription en un clic depuis les messageries (pas de jeton CSRF, URL signée).
        $middleware->validateCsrfTokens(except: ['emails/desabonnement/*']);

        $middleware->alias([
            'role' => RoleMiddleware::class,
            'active' => EnsureAccountIsActive::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        // Réponse lisible pour l'app mobile quand une limite de débit est atteinte.
        $exceptions->render(function (ThrottleRequestsException $e, Request $request) {
            if ($request->expectsJson() || $request->is('api/*')) {
                return response()->json(
                    ['message' => 'Trop de tentatives. Réessayez dans quelques instants.'],
                    429,
                    $e->getHeaders(),
                );
            }
        });
    })->create();
