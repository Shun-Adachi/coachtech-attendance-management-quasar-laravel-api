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
//Route::middleware('guest')->group(function () {
Route::post('/login', [UserAuthController::class, 'login']);      // step1
Route::post('/login/2fa', [UserAuthController::class, 'login2fa']); // step2
Route::post('/register', [UserAuthController::class, 'register']);
//});


// 認証ルート
Route::middleware('auth:sanctum')->group(function () {

    Route::get('/attendance/today-debug', [UserAttendanceController::class, 'today']);
    Route::get('/attendance/today', [UserAttendanceController::class, 'today']);
    //Route::post('/login', [UserAuthController::class, 'login']);      // step1

    // 一般ユーザー専用 API
    // Route::middleware('role:' . config('constants.ROLE_USER'))->group(function () {


    // 打刻
    Route::post('/attendance/clock-in', [userAttendanceController::class, 'clockIn']);

    // 今日の打刻情報取得
    Route::get('/attendance/today', [userAttendanceController::class, 'today']);
});
