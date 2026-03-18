<?php

use App\Http\Middleware\AdminMiddleware;
use App\Http\Middleware\UserMiddleware;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Request;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->alias([
            'admin' => AdminMiddleware::class,
            'user' => UserMiddleware::class,
        ]);

        // authミドルウェアに弾かれた未ログインユーザーの遷移先
        $middleware->redirectGuestsTo(function (Request $request) {
            if ($request->route()?->named('admin.*')) {
                return route('admin.login');
            }

            return route('login');
        });

        // guestミドルウェアに弾かれたログイン済みユーザーの遷移先
        $middleware->redirectUsersTo(function (Request $request) {
            $user = $request->user();

            if ($user->role === 'admin') {
                return route('admin.attendance.list');
            }

            if (! $user->hasVerifiedEmail()) {
                return route('verification.notice');
            }

            return route('attendance.index');
        });
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        //
    })
    ->withProviders([App\Providers\FortifyServiceProvider::class,])
    ->create();
