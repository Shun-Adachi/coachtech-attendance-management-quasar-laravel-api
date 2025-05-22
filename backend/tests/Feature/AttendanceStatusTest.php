<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use App\Models\User;
use App\Models\Attendance;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class AttendanceStatusTest extends TestCase
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
     * 勤務外の場合、勤怠ステータスが正しく「勤務外」と表示されるか検証するテスト
     */
    public function test_attendance_status_shows_kimugai_if_no_record_exists()
    {
        $user = User::where('role_id', config('constants.ROLE_USER'))->first();
        $this->actingAs($user);
        $response = $this->actingAs($user)->get('/attendance');
        $response->assertStatus(200);
        $response->assertSee('勤務外');
    }

    /**
     * ステータスが出勤中の場合、勤怠ステータスが正しく表示されることを検証するテスト
     */
    public function test_attendance_status_shows_working_if_status_is_working()
    {
        $user = User::where('role_id', config('constants.ROLE_USER'))->first();
        $workingStatusId = DB::table('statuses')->where('name', '勤務中')->value('id');
        $today = Carbon::today();
        Attendance::create([
            'user_id' => $user->id,
            'status_id' => $workingStatusId,
            'attendance_at' => $today,
        ]);

        $response = $this->actingAs($user)->get('/attendance');
        $response->assertStatus(200);
        $response->assertSee('勤務中');
    }

     /**
     * ステータスが休憩中の場合、勤怠ステータスが正しく表示されることを検証するテスト
     */
    public function test_attendance_status_shows_break_if_status_is_break()
    {
        $user = User::where('role_id', config('constants.ROLE_USER'))->first();
        $this->assertNotNull($user, 'role_id = ROLE_USER のユーザーが見つかりません。');
        $breakStatusId = DB::table('statuses')->where('name', '休憩中')->value('id');
        $today = Carbon::today();
        Attendance::create([
            'user_id' => $user->id,
            'status_id' => $breakStatusId,
            'attendance_at' => $today,
        ]);

        $response = $this->actingAs($user)->get('/attendance');
        $response->assertStatus(200);
        $response->assertSee('休憩中');
    }


    /**
     * ステータスが退勤済の場合、勤怠ステータスが正しく表示されることを検証するテスト
     */
    public function test_attendance_status_shows_leaving_if_status_is_leaving()
    {
        $user = User::where('role_id', config('constants.ROLE_USER'))->first();
        $leavingStatusId = DB::table('statuses')->where('name', '退勤済み')->value('id');
        $today = Carbon::today();
        Attendance::create([
            'user_id' => $user->id,
            'status_id' => $leavingStatusId,
            'attendance_at' => $today,
        ]);

        $response = $this->actingAs($user)->get('/attendance');
        $response->assertStatus(200);
        $response->assertSee('退勤済');
    }
}
