<?php

use Illuminate\Support\Facades\Route;

use App\Http\Controllers\CustomAuthenticatedSessionController;
use App\Http\Controllers\AdminAttendanceController;
use App\Http\Controllers\UserAttendanceController;

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Here is where you can register web routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| contains the "web" middleware group. Now create something great!
|
*/

// ルートパターンを設定（数値のみ許可）
Route::pattern('user_id', '[0-9]+');
Route::pattern('attendance_id', '[0-9]+');

// Fortify カスタマイズログイン
Route::get('/login', [CustomAuthenticatedSessionController::class, 'showUserLogin'])->middleware('guest')->name('login');
Route::post('/login', [CustomAuthenticatedSessionController::class, 'storeUser']);
Route::get('/admin/login', [CustomAuthenticatedSessionController::class, 'showAdminLogin'])->middleware('guest')->name('admin.login');
Route::post('/admin/login', [CustomAuthenticatedSessionController::class, 'storeAdmin']);
Route::get('/verify-login', [CustomAuthenticatedSessionController::class, 'verifyLogin']);

Route::middleware('auth')->group(function () {
    // ログアウト
    Route::get('/logout', [CustomAuthenticatedSessionController::class, 'logout'])->name('logout');

    // 管理者専用ルート
    Route::middleware('role:' . config('constants.ROLE_ADMIN'))->group(function () {
        Route::get('/admin/attendance/list/{year?}/{month?}/{day?}', [AdminAttendanceController::class, 'index'])->name('admin.attendance.list');
        Route::get('/admin/staff/list', [AdminAttendanceController::class, 'showStaffIndex'])->name('admin.staff.list');
        Route::get('/admin/attendance/staff/{user_id}/{year?}/{month?}', [AdminAttendanceController::class, 'showStaffAttendance'])->name('admin.attendance.staff');
        Route::post('/admin/attendance/staff/export', [AdminAttendanceController::class, 'exportStaffAttendance'])->name('admin.attendance.staff.export');
        Route::get('/stamp_correction_request/approve/{attendance_id}', [AdminAttendanceController::class, 'showApproval'])->name('stamp_correction_request.approve.show');
        Route::post('/stamp_correction_request/approve/{attendance_id}', [AdminAttendanceController::class, 'approve'])->name('stamp_correction_request.approve');
    });

    // 一般ユーザー専用ルート
    Route::middleware('role:' . config('constants.ROLE_USER'))->group(function () {
        Route::get('/attendance', [UserAttendanceController::class, 'create'])->name('attendance');
        Route::post('/attendance', [UserAttendanceController::class, 'register'])->name('attendance');
        Route::post('/attendance/break', [UserAttendanceController::class, 'break'])->name('attendance.break');
        Route::get('/attendance/list/{year?}/{month?}', [UserAttendanceController::class, 'index'])->name('attendance.list');
    });

    // 処理の異なる共通ルート
    Route::middleware(['route.role:'  . AdminAttendanceController::class . ',' . UserAttendanceController::class . ',showAttendance,showAttendance'])
        ->get('/attendance/{attendance_id}', function () {})->name('attendance.show');
    Route::middleware(['route.role:' . AdminAttendanceController::class . ',' . UserAttendanceController::class . ',submitCorrection,submitCorrection'])
        ->post('/attendance/stamp_correction_request', function () {})->name('attendance.stamp_correction_request');
    Route::middleware(['route.role:' . AdminAttendanceController::class . ',' . UserAttendanceController::class . ',showRequests,showRequests'])
        ->get('/stamp_correction_request/list', function () {})->name('stamp_correction_request.list');
});
