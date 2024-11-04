<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;

$app = Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        channels: __DIR__.'/../routes/channels.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware) {
        $middleware->validateCsrfTokens(except: [
            '/', // Replace with your actual route name
        ]);
        $middleware->append(
            \App\Http\Middleware\CheckTokenInactivity::class
        );
         $middleware->append(\App\Http\Middleware\JsonResponse::class);
        // You can append other middleware here if needed
    })
    ->withExceptions(function (Exceptions $exceptions) {
        // Handle exceptions here if needed
    });

// Create the application instance
return $app->create();