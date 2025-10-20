<?php

use Inertia\Inertia;
use App\Http\Middleware\HandleInertiaRequests;
use Illuminate\Foundation\Application;
use Illuminate\Http\Request;
use Illuminate\Auth\AuthenticationException;
use Symfony\Component\HttpFoundation\Response;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->web(append: [
            HandleInertiaRequests::class,
        ]);
        
        // Enable session and cookie encryption for API routes (for session-based auth)
        $middleware->api(prepend: [
            \Illuminate\Cookie\Middleware\EncryptCookies::class,
            \Illuminate\Cookie\Middleware\AddQueuedCookiesToResponse::class,
            \Illuminate\Session\Middleware\StartSession::class,
        ]);
        
        $middleware->alias([
            'role' => \App\Http\Middleware\RoleMiddleware::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        // Handle authentication exceptions
        $exceptions->renderable(function (AuthenticationException $e, Request $request) {
            // For API requests, return JSON
            if ($request->expectsJson() || $request->is('api/*')) {
                return response()->json([
                    'message' => $e->getMessage() ?: 'Unauthenticated.'
                ], 401);
            }
            
            // For web requests, redirect to login
            return redirect()->guest(route('login'));
        });
        
        $exceptions->respond(function (Response $response, Throwable $exception, Request $request) {
            // For API routes, always return JSON
            if ($request->is('api/*')) {
                if ($response->getStatusCode() === 401) {
                    return response()->json([
                        'message' => 'Unauthenticated.'
                    ], 401);
                }
                return $response;
            }
            
            // For web routes, use Inertia error pages
            if (in_array($response->getStatusCode(), [500, 503, 404, 403])) {
                return Inertia::render('system/error', ['status' => $response->getStatusCode()])
                    ->toResponse($request)
                    ->setStatusCode($response->getStatusCode());
            } elseif ($response->getStatusCode() === 419) {
                return back()->with([
                    'message' => 'The page expired, please try again.',
                ]);
            }

            return $response;
        });
    })
    ->create();
