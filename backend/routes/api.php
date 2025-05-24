<?php

use Illuminate\Support\Facades\Route;

use App\Http\Controllers\Api\User\AuthController   as UserAuthController;
use App\Http\Controllers\Api\Admin\AuthController  as AdminAuthController;

use App\Http\Controllers\Api\Admin\AttendanceController as AdminAttendanceController;
use App\Http\Controllers\Api\Admin\StaffController      as AdminStaffController;
use App\Http\Controllers\Api\Admin\StampCorrectionController   as AdminStampController;
use App\Http\Controllers\Api\User\AttendanceController as UserAttendanceController;
use App\Http\Controllers\Api\User\StampCorrectionController    as UserStampController;

// ルートパラメータ制約は必要ならここでも定義できます
Route::pattern('user_id', '[0-9]+');
Route::pattern('attendance_id', '[0-9]+');

// ユーザー用認証API
Route::middleware('guest')->group(function () {
    Route::post('/login',    [UserAuthController::class, 'login']);
    Route::post('/register', [UserAuthController::class, 'register']);
    Route::post('/admin/login',   [AdminAuthController::class, 'login']);
});

// 認証ルート
Route::middleware('auth:sanctum')->group(function () {

    Route::post('/logout',        [UserAuthController::class, 'logout']);
    Route::get('/verify-login',   [UserAuthController::class, 'verify']);
    Route::get('/admin/verify',   [AdminAuthController::class, 'verify']);

    // 管理者専用 API
    Route::middleware('role:' . config('constants.ROLE_ADMIN'))->group(function () {

        // 勤怠一覧
        Route::get(
            '/admin/attendance/list/{year?}/{month?}/{day?}',
            [AdminAttendanceController::class, 'index']
        );

        // スタッフ一覧
        Route::get(
            '/admin/staff/list',
            [AdminStaffController::class, 'index']
        );

        // スタッフ別勤怠
        Route::get(
            '/admin/attendance/staff/{user_id}/{year?}/{month?}',
            [AdminStaffController::class, 'showAttendance']
        );

        // スタッフ勤怠エクスポート
        Route::post(
            '/admin/attendance/staff/export',
            [AdminStaffController::class, 'export']
        );

        // /api/stamp_correction_request/approve/{attendance_id}
        Route::get(
            '/stamp_correction_request/approve/{attendance_id}',
            [AdminStampController::class, 'show']
        );
        Route::post(
            '/stamp_correction_request/approve/{attendance_id}',
            [AdminStampController::class, 'approve']
        );
    });

    // 一般ユーザー専用 API
    Route::middleware('role:' . config('constants.ROLE_USER'))->group(function () {
        // 年月指定の一覧表示
        Route::get(
            '/attendance/list/{year?}/{month?}',
            [UserAttendanceController::class, 'index']
        );

        // 今日の打刻情報取得
        Route::get(
            '/attendance',
            [UserAttendanceController::class, 'show']
        );

        // 出勤打刻
        Route::post(
            '/attendance',
            [UserAttendanceController::class, 'store']
        );

        // 休憩開始・終了打刻
        Route::post(
            '/attendance/break',
            [UserAttendanceController::class, 'update']
        );
    });

    // 処理の異なる共通ルート
    // 1) 勤怠詳細取得
    Route::middleware([
        'route.role:'
            . AdminAttendanceController::class . ','
            . UserAttendanceController::class . ','
            . 'show,show'   // showAttendance → show
    ])->get(
        '/attendance/{attendance_id}',
        function () {}
    )->name('attendance.show');

    // 2) 打刻修正リクエスト提出
    Route::middleware([
        'route.role:'
            . AdminStampController::class . ','
            . UserStampController::class . ','
            . 'approve,store'
    ])->post(
        '/attendance/stamp_correction_request',
        function () {}
    )->name('attendance.stamp_correction_request');

    // 3) 修正リクエスト一覧取得
    Route::middleware([
        'route.role:'
            . AdminStampController::class . ','
            . UserStampController::class . ','
            . 'index,index'
    ])->get(
        '/stamp_correction_request/list',
        function () {}
    )->name('stamp_correction_request.list');
});
