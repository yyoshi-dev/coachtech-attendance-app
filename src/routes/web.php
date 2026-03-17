<?php

use App\Http\Controllers\Admin\AttendanceController as AdminAttendanceController;
use App\Http\Controllers\User\AttendanceController;
use Illuminate\Support\Facades\Route;
use Laravel\Fortify\Http\Controllers\AuthenticatedSessionController;

// 認証関連
Route::get('/admin/login', [AuthenticatedSessionController::class, 'create'])
    ->middleware('guest')
    ->name('admin.login');

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
        Route::get('/attendance', [AttendanceController::class, 'showAttendancePage'])
            ->name('attendance.index');
    }
);

// 管理者の画面
Route::prefix('admin')
    ->name('admin.')
    ->middleware('auth')->group(
    function () {
        Route::get('/attendance/list', [AdminAttendanceController::class, 'showAttendanceList'])
            ->name('attendance.list');
    }
);


// ⚠️ 開発が終わったら必ず消してください！
Route::get('/logout', [Laravel\Fortify\Http\Controllers\AuthenticatedSessionController::class, 'destroy']);