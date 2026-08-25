<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Request;
use Makeroi\Common\Http\Middleware\ForceJsonResponseMiddleware;
use Makeroi\Common\Http\Middleware\WithExtraContentMiddleware;
use Makeroi\Common\Http\Middleware\ResponseFormatMiddleware;
use Illuminate\Routing\Middleware\SubstituteBindings;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->group('api', [
            ForceJsonResponseMiddleware::class,
            WithExtraContentMiddleware::class,
            ResponseFormatMiddleware::class,
            SubstituteBindings::class
        ]);
    })

    ->withExceptions(function (Exceptions $exceptions): void {
        $exceptions->shouldRenderJsonWhen(
            fn (Request $request) => $request->is('api/*'),
        );
    })->create();
