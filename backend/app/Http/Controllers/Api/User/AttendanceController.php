<?php

namespace App\Http\Controllers\Api\User;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use App\Models\Attendance;
use Illuminate\Support\Facades\Auth;

class AttendanceController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        //
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        //
    }

    /**
     * Display the specified resource.
     */
    public function show(string $id)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, string $id)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        //
    }

    /**
     * 本日の出勤を記録
     *
     * @param  Request  $request
     * @return JsonResponse
     */
    public function clockIn(Request $request): JsonResponse
    {
        $user = $request->user();
        $today = now()->toDateString();

        // 既に本日の出勤データがあるかチェック
        $exists = Attendance::where('user_id', $user->id)
            ->whereDate('attendance_at', $today)
            ->exists();

        if ($exists) {
            return response()->json([
                'message' => '本日の出勤はすでに記録されています'
            ], 409);
        }

        // 出勤ステータスID (例: 2 は "勤務中" と仮定)
        $workingStatusId = config('constants.STATUS_WORKING');

        // レコード作成
        $attendance = Attendance::create([
            'user_id'       => $user->id,
            'status_id'     => $workingStatusId,
            'attendance_at' => $today,
            'clock_in'      => now()->format('H:i:s'),
        ]);

        return response()->json([
            'message'    => '出勤を記録しました',
            'attendance' => $attendance,
        ], 201);
    }

    /**
     * 本日の勤怠データを取得
     *
     * @param  Request  $request
     * @return JsonResponse
     */
    public function today(Request $request): JsonResponse
    {
        $user = $request->user();
        $check = $request->user() !== null;
        // デバッグ用ダンプ
        \Log::info('today() debug', [
            'cookies'      => $request->cookies->all(),
            'session_user' => $user,
            'auth_check'   => $check,
        ]);
        $user = $request->user();
        $today = now()->toDateString();

        $attendance = Attendance::with('status')
            ->where('user_id', $user->id)
            ->whereDate('attendance_at', $today)
            ->first();

        if (! $attendance) {
            return response()->json(null, 204);
        }

        return response()->json($attendance, 200);
    }
}
