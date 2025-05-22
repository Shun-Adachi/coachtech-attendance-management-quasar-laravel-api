<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use App\Models\User;
use App\Models\Attendance;
use App\Models\Status;
use Carbon\Carbon;

class AttendanceClockOutTest extends TestCase
{
    use RefreshDatabase;

    /**
     * シーディングを有効にするための設定
     *
     * @return bool
     */
    protected function shouldSeed()
    {
        return true;
    }

    /**
     * 退勤ボタンが正しく機能するテスト
     */
    public function test_user_can_clock_out_when_status_is_working()
    {
        // テストデータの準備
        $this->withoutMiddleware(\App\Http\Middleware\VerifyCsrfToken::class);
        $user = User::where('role_id', config('constants.ROLE_USER'))->first();
        $workingStatusId    = Status::where('name', '勤務中')->value('id');
        $leavingStatusId    = Status::where('name', '退勤済み')->value('id');
        $today = Carbon::today();
        Attendance::create([
            'user_id'       => $user->id,
            'status_id'     => $workingStatusId,
            'attendance_at' => $today,
        ]);
        $this->actingAs($user);

        // 退勤処理前の確認
        $responseBeforeClockOut = $this->get('/attendance');
        $responseBeforeClockOut->assertStatus(200);
        $responseBeforeClockOut->assertSee('退勤');

        // 退勤処理後の確認
        $this->post('/attendance')->assertRedirect('/attendance');
        $responseAfterClockOut = $this->get('/attendance');
        $responseAfterClockOut->assertStatus(200);
        $responseAfterClockOut->assertSee('退勤済');
        $updatedAttendance = Attendance::where('user_id', $user->id)
            ->whereDate('attendance_at', $today)
            ->first();
        $this->assertEquals($leavingStatusId, $updatedAttendance->status_id, 'ステータスが「退勤済み」に更新されていません。');
        $this->assertNotNull($updatedAttendance->clock_out, '退勤時刻(clock_out)が記録されていません。');
    }

    /**
     * 退勤時刻が管理画面で確認できるテスト
     */
    public function test_clock_out_is_recorded_in_management_screen()
    {
        // テストデータの準備
        $this->withoutMiddleware(\App\Http\Middleware\VerifyCsrfToken::class);
        $user = User::where('role_id', config('constants.ROLE_USER'))->first();
        $workingStatusId    = Status::where('name', '勤務中')->value('id');
        $today = Carbon::today();
        Attendance::create([
            'user_id'       => $user->id,
            'status_id'     => $workingStatusId,
            'attendance_at' => $today,
        ]);

        // 退勤処理前の確認
        $responseBeforeClockOut = $this->actingAs($user)->get('/attendance');
        $responseBeforeClockOut->assertStatus(200);
        $responseBeforeClockOut->assertSee('退勤');

        // 出勤処理と出勤時間の確認(テストなのでCarbonによるデータ取得との差を2秒まで許容)
        $clockIn = Carbon::now();
        $this->post('/attendance')->assertRedirect('/attendance');
        $today = Carbon::today();
        $updatedAttendance = Attendance::where('user_id', $user->id)
            ->whereDate('attendance_at', $today)
            ->first();
        $recordedClockOut = Carbon::parse($updatedAttendance->clock_out);
        $ClockOutDiff = $clockIn->diffInSeconds($recordedClockOut);
        $this->assertTrue($ClockOutDiff <= 1, "休憩入処理時刻の差が大きすぎます: {$ClockOutDiff}秒");

        // 勤怠一覧画面の退勤時間を確認
        $listResponse = $this->get('/attendance/list');
        $listResponse->assertStatus(200);
        $expectedClockOut = Carbon::parse($updatedAttendance->clock_out)->format('H:i');
        $listResponse->assertSee($expectedClockOut);
    }
}
