<?php

use App\Http\Controllers\Admin\AttendanceController as AdminAttendanceController;
use App\Http\Controllers\Admin\StaffController;
use App\Http\Controllers\User\AttendanceController;
use App\Http\Controllers\User\CorrectionRequestController;
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
Route::middleware(['auth', 'verified', 'user'])->group(function () {
    Route::get('/attendance', [AttendanceController::class, 'index'])
        ->name('attendance.index');
    Route::post('/attendance/clock-in', [AttendanceController::class, 'clockIn'])
        ->name('attendance.clock-in');
    Route::post('/attendance/clock-out', [AttendanceController::class, 'clockOut'])
        ->name('attendance.clock-out');
    Route::post('/attendance/break-start', [AttendanceController::class, 'breakStart'])
        ->name('attendance.break-start');
    Route::post('/attendance/break-end', [AttendanceController::class, 'breakEnd'])
        ->name('attendance.break-end');
    Route::get('/attendance/list', [AttendanceController::class, 'list'])
        ->name('attendance.list');
    Route::get('/attendance/detail/{id}', [AttendanceController::class, 'detail'])
        ->name('attendance.detail');
    Route::post('/attendance/detail/{id}', [CorrectionRequestController::class, 'storeCorrection'])
        ->name('attendance.corrections.store');
    Route::get('/stamp_correction_request/list', [CorrectionRequestController::class, 'indexCorrections'])
        ->name('attendance.corrections.index');
});

// 管理者の画面
Route::prefix('admin')
    ->name('admin.')
    ->middleware(['auth', 'admin'])
    ->group(function () {
        Route::get('/attendance/list', [AdminAttendanceController::class, 'dailyList'])
            ->name('attendance.list');
        Route::get('/attendance/{id}', [AdminAttendanceController::class, 'detail'])
            ->name('attendance.detail');
        Route::put('/attendance/{id}', [AdminAttendanceController::class, 'update'])
            ->name('attendance.update');
        Route::get('/staff/list', [StaffController::class, 'list'])
            ->name('staff.list');
        Route::get('/attendance/staff/{id}', [AdminAttendanceController::class, 'staffMonthlyList'])
            ->name('attendance.staff.monthly');
        Route::get('/attendance/staff/{id}/export', [AdminAttendanceController::class, 'export'])
            ->name('attendance.staff.monthly.export');
    });