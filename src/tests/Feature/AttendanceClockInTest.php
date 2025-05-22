<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use App\Models\User;
use App\Models\Attendance;
use App\Models\Status;
use Carbon\Carbon;

class AttendanceClockInTest extends TestCase
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
     * ステータスが勤務外のユーザーにログインし、
     * 画面に「出勤」ボタンが表示されていることを確認したうえで、
     * 出勤の処理を行うとステータスが「勤務中」になることをテスト
     */
    public function test_user_can_clock_in_when_status_is_before_work()
    {
        $this->withoutMiddleware(\App\Http\Middleware\VerifyCsrfToken::class);
        $user = User::where('role_id', config('constants.ROLE_USER'))->first();
        $response = $this->actingAs($user)->get('/attendance');
        $response->assertStatus(200);
        $response->assertSee('出勤');

        $this->post('/attendance')->assertRedirect('/attendance');
        $responseAfterClockIn = $this->get('/attendance');
        $response->assertStatus(200);
        $responseAfterClockIn->assertSee('勤務中');
    }

    /**
     * 出勤は一日一回のみできることを検証するテスト。
     */
    public function test_clock_in_button_is_not_displayed_if_status_is_leaving()
    {
        $user = User::where('role_id', config('constants.ROLE_USER'))->first();
        $this->assertNotNull($user, 'role_id = ROLE_USER のユーザーが見つかりません。');
        $leavingStatusId = Status::where('name', '退勤済み')->value('id');
        $today = Carbon::today();
        Attendance::create([
            'user_id' => $user->id,
            'status_id' => $leavingStatusId,
            'attendance_at' => $today,
        ]);

        $response = $this->actingAs($user)->get('/attendance');
        $response->assertStatus(200);
        $response->assertDontSee('出勤');
    }

    /**
     * 出勤時刻が管理画面で確認できることを検証するテスト
     */
    public function test_clock_in_is_recorded_in_management_screen()
    {
        // テストの準備
        $this->withoutMiddleware(\App\Http\Middleware\VerifyCsrfToken::class);
        $user = User::where('role_id', config('constants.ROLE_USER'))->first();
        $workingStatusId    = Status::where('name', '勤務中')->value('id');
        $response = $this->actingAs($user)->get('/attendance');
        $response->assertStatus(200);

        // 出勤処理と出勤時間の確認(テストなのでCarbonによるデータ取得との差を2秒まで許容)
        $clockIn = Carbon::now();
        $this->post('/attendance')->assertRedirect('/attendance');
        $today = Carbon::today();
        $attendance = Attendance::where('user_id', $user->id)
            ->whereDate('attendance_at', $today)
            ->first();
        $this->assertNotNull($attendance, '休憩レコードが作成されていません。');
        $recordedClockIn = Carbon::parse($attendance->clock_in);
        $ClockInDiff = $clockIn->diffInSeconds($recordedClockIn);
        $this->assertTrue($ClockInDiff <= 1, "休憩入処理時刻の差が大きすぎます: {$ClockInDiff}秒");

        // 勤怠一覧画面の出勤時間の確認
        $listResponse = $this->get('/attendance/list');
        $listResponse->assertStatus(200);
        $updatedAttendance = Attendance::where('user_id', $user->id)
            ->whereDate('attendance_at', $today)
            ->first();
        $this->assertNotNull($updatedAttendance->clock_in, 'clock_in が保存されていません。');
        $this->assertEquals($workingStatusId, $updatedAttendance->status_id, 'ステータスが「勤務中」に更新されていません。');
        $clockInFormatted = Carbon::parse($updatedAttendance->clock_in)->format('H:i');
        $listResponse->assertSee($clockInFormatted);
    }
}
