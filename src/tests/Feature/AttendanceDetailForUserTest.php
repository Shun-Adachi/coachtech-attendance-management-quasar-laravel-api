<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use App\Models\User;
use App\Models\BreakTime;
use App\Models\Attendance;
use Carbon\Carbon;
use Tests\TestCase;

class AttendanceDetailForUserTest extends TestCase
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
     * 勤怠詳細画面の「名前」がログインユーザーの氏名になっていることを検証するテスト
     */
    public function test_attendance_detail_shows_logged_in_user_name()
    {
        $user = User::where('role_id', config('constants.ROLE_USER'))->first();
        $today = Carbon::today();
        $attendance = Attendance::create([
            'user_id'       => $user->id,
            'status_id'     => config('constants.STATUS_WORKING'),
            'attendance_at' => $today,
            'clock_in'      => '09:00:00',
            'clock_out'     => '18:00:00',
        ]);
        $this->actingAs($user);
        $response = $this->get("/attendance/{$attendance->id}");
        $response->assertStatus(200);
        $response->assertSee($user->name);
    }

    /**
     * 勤怠詳細画面の「日付」が、選択した日付になっていることを検証するテスト
     */
    public function test_attendance_detail_shows_selected_date()
    {
        $user = User::where('role_id', config('constants.ROLE_USER'))->first();
        $today = Carbon::today();
        $attendance = Attendance::create([
            'user_id'       => $user->id,
            'status_id'     => config('constants.STATUS_WORKING'),
            'attendance_at' => $today,
            'clock_in'      => '09:00:00',
            'clock_out'     => '18:00:00',
        ]);
        $this->actingAs($user);
        $response = $this->get("/attendance/{$attendance->id}");
        $response->assertStatus(200);
        $expectedDate = $today->format('n月j日');
        $response->assertSee($expectedDate);
    }

    /**
     * 勤怠詳細画面の「出勤・退勤」に記されている時間が、
     * ログインユーザーの打刻時刻と一致していることを検証するテスト
     */
    public function test_attendance_detail_shows_clock_times_matching_user_punches()
    {
        $user = User::where('role_id', config('constants.ROLE_USER'))->first();
        $today = Carbon::today();
        $attendance = Attendance::create([
            'user_id'       => $user->id,
            'status_id'     => config('constants.STATUS_WORKING'),
            'attendance_at' => $today,
            'clock_in'      => '09:00:00',
            'clock_out'     => '18:00:00',
        ]);
        $this->actingAs($user);
        $response = $this->get("/attendance/{$attendance->id}");
        $response->assertStatus(200);
        $expectedClockIn  = Carbon::parse($attendance->clock_in)->format('H:i');
        $expectedClockOut = Carbon::parse($attendance->clock_out)->format('H:i');
        $response->assertSee($expectedClockIn);
        $response->assertSee($expectedClockOut);
    }

    /**
     * 勤怠詳細画面の「休憩」欄に記されている時間が、ログインユーザーの打刻（休憩入・戻り時刻）と一致していることを検証するテスト
     */
    public function test_attendance_detail_break_section_shows_correct_break_times()
    {
        // テストデータの準備準備
        $user = User::where('role_id', config('constants.ROLE_USER'))->first();
        $today = Carbon::today();
        $attendance = Attendance::create([
            'user_id'       => $user->id,
            'status_id'     => config('constants.STATUS_WORKING'),
            'attendance_at' => $today,
            'clock_in'      => '09:00:00',
            'clock_out'     => '18:00:00',
        ]);
        $breakTime = BreakTime::create([
            'attendance_id' => $attendance->id,
            'break_in'      => '10:00:00',
            'break_out'     => '10:30:00',
        ]);
        $this->actingAs($user);

        // 勤怠詳細画面の確認
        $response = $this->get("/attendance/{$attendance->id}");
        $response->assertStatus(200);
        $expectedBreakIn  = Carbon::parse($breakTime->break_in)->format('H:i');   // "10:00"
        $expectedBreakOut = Carbon::parse($breakTime->break_out)->format('H:i');  // "10:30"
        $response->assertSee($expectedBreakIn);
        $response->assertSee($expectedBreakOut);
    }
}
