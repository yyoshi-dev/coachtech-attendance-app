<?php

use Illuminate\Support\Facades\Route;
use Laravel\Fortify\Http\Controllers\AuthenticatedSessionController;

Route::get('/', function () {
    return view('welcome');
});

// 認証関連
Route::get('/admin/login', [AuthenticatedSessionController::class, 'create'])
    ->middleware('guest')
    ->name('admin.login');

Route::post('/admin/login', [AuthenticatedSessionController::class, 'store'])
    ->middleware('guest')
    ->name('admin.login.store');

Route::post('/admin/logout', [AuthenticatedSessionController::class, 'destroy'])
    ->middleware(['auth', 'admin'])
    ->name('admin.logout');

Route::get('/email/verify', function () {
    return view('user.auth.verify-email');
})->middleware('auth')->name('verification.notice');

Route::get('/email/verify/mailhog', function () {
    return redirect(config('services.mailhog.url'));
})->middleware('auth')->name('verification.mailhog');

// 一般ユーザーの画面
Route::middleware(['auth', 'verified'])->group(
    function () {
        Route::get('/attendance', function () {
            return view('welcome');
        })->name('attendance.index'); // 仮置き
    }
);

// 管理者の画面
Route::prefix('admin')
    ->name('admin.')
    ->middleware('auth')->group(
    function () {
        Route::get('/attendance/list', function () {
            return view('welcome');
        })->name('attendance.list'); // 仮置き
    }
);


// ⚠️ 開発が終わったら必ず消してください！
Route::get('/logout', [Laravel\Fortify\Http\Controllers\AuthenticatedSessionController::class, 'destroy']);