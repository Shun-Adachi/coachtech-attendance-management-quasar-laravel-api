<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use App\Models\User;
use App\Models\Attendance;
use App\Models\Status;
use App\Models\BreakTime;
use Carbon\Carbon;

class AttendanceBreakTest extends TestCase
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
     * 休憩ボタンが正しく機能するテスト
     */
    public function test_user_can_start_break_when_status_is_working()
    {
        // テストデータの準備
        $this->withoutMiddleware(\App\Http\Middleware\VerifyCsrfToken::class);
        $user = User::where('role_id', config('constants.ROLE_USER'))->first();
        $workingStatusId    = Status::where('name', '勤務中')->value('id');
        $breakStatusId    = Status::where('name', '休憩中')->value('id');
        $today = Carbon::today();
        Attendance::create([
            'user_id'       => $user->id,
            'status_id'     => $workingStatusId,
            'attendance_at' => $today,
        ]);
        $this->actingAs($user);

        // 休憩処理前の確認
        $response = $this->get('/attendance');
        $response->assertStatus(200);
        $response->assertSee('休憩入');

        // 休憩処理後の確認
        $this->post('/attendance/break')->assertRedirect('/attendance');
        $responseAfterBreak = $this->get('/attendance');
        $responseAfterBreak->assertStatus(200);
        $responseAfterBreak->assertSee('休憩中');
        $updatedAttendance = Attendance::where('user_id', $user->id)
            ->whereDate('attendance_at', $today)
            ->first();
        $this->assertEquals($breakStatusId, $updatedAttendance->status_id, 'ステータスが「休憩中」に更新されていません。');
        $breakTime = BreakTime::where('attendance_id', $updatedAttendance->id)->first();
        $this->assertNotNull($breakTime, '休憩用のBreakTimeレコードが作成されていません。');
        $this->assertNotNull($breakTime->break_in, 'break_in が記録されていません。');
        $this->assertNull($breakTime->break_out, 'break_out は休憩戻り時に記録されるはずが、既に入っています。');
    }

    /**
     * 休憩は一日に何回でもできることを検証するテスト。
     */
    public function test_user_can_take_multiple_breaks_in_one_day()
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
        $this->actingAs($user);

        // 休憩処理前の確認
        $responseBeforeBreakIn = $this->get('/attendance');
        $responseBeforeBreakIn->assertStatus(200);
        $responseBeforeBreakIn->assertSee('休憩入');

        // 休憩処理後の確認
        $this->post('/attendance/break')->assertRedirect('/attendance');
        $responseAfterBreakIn = $this->get('/attendance');
        $responseAfterBreakIn->assertStatus(200);
        $responseAfterBreakIn->assertSee('休憩戻');

        // 休憩戻処理後の確認
        $this->post('/attendance/break')->assertRedirect('/attendance');
        $responseBreakOut = $this->get('/attendance');
        $responseBreakOut->assertStatus(200);
        $responseBreakOut->assertSee('休憩入');
    }

    /**
     * 休憩戻ボタンが正しく機能するテスト
     */
    public function test_user_can_return_from_break_to_working_status()
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
        $this->actingAs($user);

        // 休憩処理前の確認
        $responseBeforeBreakIn = $this->get('/attendance');
        $responseBeforeBreakIn->assertStatus(200);
        $responseBeforeBreakIn->assertSee('休憩入');

        // 休憩処理後の確認
        $this->post('/attendance/break')->assertRedirect('/attendance');
        $responseAfterBreakIn = $this->get('/attendance');
        $responseAfterBreakIn->assertStatus(200);
        $responseAfterBreakIn->assertSee('休憩戻');

        // 休憩戻処理後の確認
        $this->post('/attendance/break')->assertRedirect('/attendance');
        $responseBreakOut = $this->get('/attendance');
        $responseBreakOut->assertStatus(200);
        $responseBreakOut->assertSee('休憩入');
        $updatedAttendance = Attendance::where('user_id', $user->id)
            ->whereDate('attendance_at', $today)
            ->first();
        $this->assertEquals($workingStatusId, $updatedAttendance->status_id, '最終的にステータスが「勤務中」に戻っていません。');
        $breakTimeRecords = BreakTime::where('attendance_id', $updatedAttendance->id)->get();
        $this->assertCount(1, $breakTimeRecords, '休憩レコードの数が想定と異なります。');
        $this->assertNotNull($breakTimeRecords->first()->break_in, '休憩入り時間が記録されていません。');
        $this->assertNotNull($breakTimeRecords->first()->break_out, '休憩戻り時間が記録されていません。');
    }

    /**
     * 休憩戻は一日に何回でもできることを検証するテスト。
     */
    public function test_user_can_return_from_break_multiple_times()
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
        $this->actingAs($user);

        // 休憩処理前の確認
        $responseBeforeBreakIn = $this->get('/attendance');
        $responseBeforeBreakIn->assertStatus(200);
        $responseBeforeBreakIn->assertSee('休憩入');

        // 休憩処理後の確認
        $this->post('/attendance/break')->assertRedirect('/attendance');
        $responseAfterBreakIn = $this->get('/attendance');
        $responseAfterBreakIn->assertStatus(200);
        $responseAfterBreakIn->assertSee('休憩戻');

        // 休憩戻り処理後の確認
        $this->post('/attendance/break')->assertRedirect('/attendance');
        $responseBreakOut = $this->get('/attendance');
        $responseBreakOut->assertStatus(200);
        $responseBreakOut->assertSee('休憩入');

        // 再度休憩処理後の確認
        $this->post('/attendance/break')->assertRedirect('/attendance');
        $responseAfterReBreakIn = $this->get('/attendance');
        $responseAfterReBreakIn->assertStatus(200);
        $responseAfterReBreakIn->assertSee('休憩戻');
    }

    /**
     * 休憩時刻が管理画面で確認できることのテスト
     */
    public function test_break_time_is_recorded_in_management_screen()
    {
        // テストデータの準備
        $this->withoutMiddleware(\App\Http\Middleware\VerifyCsrfToken::class);
        $user = User::where('role_id', config('constants.ROLE_USER'))->first();
        $workingStatusId    = Status::where('name', '勤務中')->value('id');
        $today = Carbon::today();
        $attendance = Attendance::create([
            'user_id'       => $user->id,
            'status_id'     => $workingStatusId,
            'attendance_at' => $today,
        ]);
        $this->actingAs($user);

        // 休憩処理と記録時間の確認(Carbonによるデータ取得との差を2秒まで許容)
        $breakInStart = Carbon::now();
        $this->post('/attendance/break')->assertRedirect('/attendance');
        $breakTime = BreakTime::where('attendance_id', $attendance->id)->first();
        $this->assertNotNull($breakTime, '休憩レコードが作成されていません。');
        $recordedBreakIn = Carbon::parse($breakTime->break_in);
        $breakInDiff = $breakInStart->diffInSeconds($recordedBreakIn);
        $this->assertTrue($breakInDiff <= 2, "休憩入処理時刻の差が大きすぎます: {$breakInDiff}秒");

        // 休憩戻処理と記録時間の確認(Carbonによるデータ取得との差を2秒まで許容)
        $breakOutStart = Carbon::now();
        $this->post('/attendance/break')->assertRedirect('/attendance');
        $listResponse = $this->get('/attendance/list');
        $listResponse->assertStatus(200);
        $breakTime->refresh();
        $recordedBreakOut = Carbon::parse($breakTime->break_out);
        $breakOutDiff = $breakOutStart->diffInSeconds($recordedBreakOut);
        $this->assertTrue($breakOutDiff <= 2, "休憩戻処理時刻の差が大きすぎます: {$breakOutDiff}秒");

        // --- 勤怠詳細画面での表示確認 ---
        $detailResponse = $this->get('/attendance/' . $attendance->id);
        $detailResponse->assertStatus(200);
        $expectedBreakInDisplay  = $recordedBreakIn->format('H:i');
        $expectedBreakOutDisplay = $recordedBreakOut->format('H:i');
        $detailResponse->assertSee($expectedBreakInDisplay);
        $detailResponse->assertSee($expectedBreakOutDisplay);
    }
}
