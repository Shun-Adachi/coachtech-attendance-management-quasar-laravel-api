<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use App\Models\User;
use App\Models\BreakTime;
use App\Models\Attendance;
use Carbon\Carbon;
use Tests\TestCase;

class AdminAttendanceListTest extends TestCase
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
    use RefreshDatabase;

    /**
     * 勤怠一覧情報取得機能（管理者）
     * その日になされた全ユーザーの勤怠情報が正確に確認できることを検証するテスト
     */
    public function test_admin_can_see_all_users_attendance_for_the_day()
    {
        $targetDate = Carbon::today();
        $users = User::where('role_id', config('constants.ROLE_USER'))->get();
        foreach ($users as $user) {
            $attendance = Attendance::create([
                'user_id'       => $user->id,
                'attendance_at' => $targetDate->format('Y-m-d'),
                'status_id'     => config('constants.STATUS_LEAVING'),
                'clock_in'      => '09:00',
                'clock_out'     => '18:00',
            ]);
            BreakTime::create([
                'attendance_id' => $attendance->id,
                'break_in'      => '12:00',
                'break_out'     => '12:30',
            ]);
        }

        $adminUser = User::where('role_id', config('constants.ROLE_ADMIN'))->first();
        $this->actingAs($adminUser);
        $url = "/admin/attendance/list";
        $response = $this->get($url);
        $response->assertStatus(200);

        foreach ($users as $user) {
            $response->assertSee($user->name);
            $response->assertSee('9:00');
            $response->assertSee('18:00');
            $response->assertSee('00:30');
            $response->assertSee('8:30');
        }
    }

    /**
     * 勤怠一覧画面に遷移した際に、現在の日付が表示されることを検証するテスト
     */
    public function test_attendance_list_page_displays_current_date_for_admin()
    {
        $adminUser = User::where('role_id', config('constants.ROLE_ADMIN'))->first();
        $this->actingAs($adminUser);
        $response = $this->get('/admin/attendance/list');
        $response->assertStatus(200);
        $currentDate = Carbon::now();
        $formattedDate = $currentDate->format('Y/m/d');
        $response->assertSee($formattedDate);
    }

    /**
     * 勤怠一覧情報取得機能（管理者）
     * 「前日」を押下した時に前の日の勤怠情報が表示されることを検証するテスト
     */
    public function test_admin_can_see_previous_day_attendance()
    {
        // テストデータの準備
        $currentDate = Carbon::now();
        $prevDate = $currentDate->copy()->subDay();
        $user = User::where('role_id', config('constants.ROLE_USER'))->first();
        // ダミーデータがある場合はダミーデータを使用
        $prevDateAttendance = Attendance::where('attendance_at', $prevDate->format('Y-m-d'))->first();
        // ダミー出たが無い場合は作成
        if(!$prevDateAttendance){
            $prevDateAttendance = Attendance::create([
                'user_id'       => $user->id,
                'attendance_at' => $prevDate->format('Y-m-d'),
                'status_id'     => config('constants.STATUS_LEAVING'),
                'clock_in'      => '08:45',
                'clock_out'     => '17:45',
            ]);
        }
        // 勤怠一覧画面(管理者)表示
        $adminUser = User::where('role_id', config('constants.ROLE_ADMIN'))->first();
        $this->actingAs($adminUser);
        $url = "/admin/attendance/list";
        $response = $this->get($url);
        $response->assertStatus(200);
        // 前日の勤怠一覧画面(管理者)を確認
        $prevDayUrl = "/admin/attendance/list/{$prevDate->year}/{$prevDate->month}/{$prevDate->day}";
        $response = $this->get($prevDayUrl);
        $response->assertStatus(200);
        $formattedPrevDate = $prevDate->format('Y/m/d');
        $response->assertSee($formattedPrevDate);
        $response->assertSee($user->name);
        $response->assertSee($prevDateAttendance->clock_in);
        $response->assertSee($prevDateAttendance->clock_out);
    }

    /**
     * 勤怠一覧情報取得機能（管理者）
     * 「翌日」を押下した時に次の日の勤怠情報が表示されることを検証するテスト
     */
    public function test_admin_can_see_next_day_attendance()
    {
        // テストデータの準備
        $currentDate = Carbon::now();
        $nextDate = $currentDate->copy()->subDay();
        $user = User::where('role_id', config('constants.ROLE_USER'))->first();
        $nextDateAttendance = Attendance::create([
            'user_id'       => $user->id,
            'attendance_at' => $nextDate->format('Y-m-d'),
            'status_id'     => config('constants.STATUS_LEAVING'),
            'clock_in'      => '08:45',
            'clock_out'     => '17:45',
        ]);
        // 勤怠一覧画面(管理者)表示
        $adminUser = User::where('role_id', config('constants.ROLE_ADMIN'))->first();
        $this->actingAs($adminUser);
        $url = "/admin/attendance/list";
        $response = $this->get($url);
        $response->assertStatus(200);
        // 翌日の勤怠一覧画面(管理者)を確認
        $prevDayUrl = "/admin/attendance/list/{$nextDate->year}/{$nextDate->month}/{$nextDate->day}";
        $response = $this->get($prevDayUrl);
        $response->assertStatus(200);
        $formattedPrevDate = $nextDate->format('Y/m/d');
        $response->assertSee($formattedPrevDate);
        $response->assertSee($user->name);
        $response->assertSee($nextDateAttendance->clock_in);
        $response->assertSee($nextDateAttendance->clock_out);
    }

     /**
     * 勤怠詳細情報取得・修正機能（管理者）
     * 勤怠詳細画面に表示されるデータが選択したものになっていることを検証するテスト
     */
    public function test_attendance_detail_shows_selected_data()
    {
        // テストデータの準備
        $user = User::where('role_id', config('constants.ROLE_USER'))->first();
        $attendance = Attendance::where('user_id', $user->id)->first();
        $breakTime = BreakTime::where('attendance_id', $attendance->id)->first();
        $adminUser = User::where('role_id', config('constants.ROLE_ADMIN'))->first();
        // 勤怠詳細ページの確認
        $this->actingAs($adminUser);
        $response = $this->get("/attendance/{$attendance->id}");
        $response->assertStatus(200);
        $formattedYear      = Carbon::parse($attendance->attendance_at)->format('Y年');
        $formattedDate      = Carbon::parse($attendance->attendance_at)->format('n月j日');
        $formattedClockIn   = Carbon::parse($attendance->clock_in)->format('H:i');
        $formattedClockOut  = Carbon::parse($attendance->clock_out)->format('H:i');
        $formattedBreakIn   = Carbon::parse($breakTime->break_in)->format('H:i');
        $formattedBreakOut  = Carbon::parse($breakTime->break_out)->format('H:i');
        $response->assertSee($user->name);
        $response->assertSee($formattedYear);
        $response->assertSee($formattedDate);
        $response->assertSee($formattedClockIn);
        $response->assertSee($formattedClockOut);
        $response->assertSee($formattedBreakIn);
        $response->assertSee($formattedBreakOut);
        $response->assertSee($attendance->note);
    }

    /**
     * 勤怠詳細情報修正機能（管理者）
     * 出勤時間が退勤時間より後になっている場合、エラーメッセージが表示されることを検証するテスト
     */
    public function test_invalid_clock_times_in_admin_update_shows_validation_error()
    {
        // テストデータの準備
        $this->withoutMiddleware(\App\Http\Middleware\VerifyCsrfToken::class);
        $user = User::where('role_id', config('constants.ROLE_USER'))->first();
        $attendance = Attendance::where('user_id', $user->id)->first();
        $adminUser = User::where('role_id', config('constants.ROLE_ADMIN'))->first();
        $this->actingAs($adminUser);
        $this->get("/attendance/{$attendance->id}")->assertStatus(200);

        // 不適切な勤怠データの送信
        $postData = [
            'attendance_id' => $attendance->id,
            'updated_at'    => $attendance->updated_at->format('Y-m-d H:i:s'),
            'clock_in'      => '19:00',
            'clock_out'     => '18:00',
            'note'          => '管理者による修正申請テスト',
            'breakTimes'    => [],
        ];
        $response = $this->from("/attendance/{$attendance->id}")
                         ->post('/attendance/stamp_correction_request', $postData);
        $response->assertRedirect("/attendance/{$attendance->id}");
        $response->assertSessionHasErrors([
            'clock_in' => '出勤時間もしくは退勤時間が不適切な値です',
        ]);
    }

    /**
     * 勤怠詳細情報修正機能（管理者）
     * 休憩開始時間が退勤時間より後になっている場合、エラーメッセージが表示されることを検証するテスト
     */
    public function test_invalid_break_in_times_in_admin_update_shows_validation_error()
    {
        // テストデータの準備
        $this->withoutMiddleware(\App\Http\Middleware\VerifyCsrfToken::class);
        $user = User::where('role_id', config('constants.ROLE_USER'))->first();
        $attendance = Attendance::where('user_id', $user->id)->first();
        $adminUser = User::where('role_id', config('constants.ROLE_ADMIN'))->first();
        $this->actingAs($adminUser);
        $this->get("/attendance/{$attendance->id}")->assertStatus(200);

        // 不適切な休憩開始データの送信およびレスポンスの確認
        $postData = [
            'attendance_id' => $attendance->id,
            'updated_at'    => $attendance->updated_at->format('Y-m-d H:i:s'),
            'clock_in'      => '09:00',
            'clock_out'     => '18:00',
            'note'          => '休憩修正テスト',
            'breakTimes'    => [
                'new' => [
                    'break_in'  => '19:00',
                    'break_out' => '13:00',
                ],
            ],
        ];
        $response = $this->from("/attendance/{$attendance->id}")
                         ->post('/attendance/stamp_correction_request', $postData);
        $response->assertRedirect("/attendance/{$attendance->id}");
        $response->assertSessionHasErrors([
            'clock_in' => '出勤時間もしくは退勤時間が不適切な値です',
        ]);
    }

    /**
     * 勤怠詳細情報修正機能（管理者）
     * 休憩終了時間が退勤時間より後になっている場合、エラーメッセージが表示されることを検証するテスト
     */
    public function test_invalid_break_out_times_in_admin_update_shows_validation_error()
    {
        // テストデータの準備
        $this->withoutMiddleware(\App\Http\Middleware\VerifyCsrfToken::class);
        $user = User::where('role_id', config('constants.ROLE_USER'))->first();
        $attendance = Attendance::where('user_id', $user->id)->first();
        $adminUser = User::where('role_id', config('constants.ROLE_ADMIN'))->first();
        $this->actingAs($adminUser);
        $this->get("/attendance/{$attendance->id}")->assertStatus(200);

        // 不適切な休憩開始データの送信およびレスポンスの確認
        $postData = [
            'attendance_id' => $attendance->id,
            'updated_at'    => $attendance->updated_at->format('Y-m-d H:i:s'),
            'clock_in'      => '09:00',
            'clock_out'     => '18:00',
            'note'          => '休憩修正テスト',
            'breakTimes'    => [
                'new' => [
                    'break_in'  => '12:00',
                    'break_out' => '19:00',
                ],
            ],
        ];
        $response = $this->from("/attendance/{$attendance->id}")
                         ->post('/attendance/stamp_correction_request', $postData);
        $response->assertRedirect("/attendance/{$attendance->id}");
        $response->assertSessionHasErrors([
            'clock_in' => '出勤時間もしくは退勤時間が不適切な値です',
        ]);
    }

    /**
     * 勤怠詳細情報修正機能（管理者）
     * 備考欄が未入力の場合、エラーメッセージが表示されることを検証するテスト
     */
    public function test_empty_note_field_shows_validation_error_for_admin()
    {
        // テストデータの準備
        $this->withoutMiddleware(\App\Http\Middleware\VerifyCsrfToken::class);
        $user = User::where('role_id', config('constants.ROLE_USER'))->first();
        $attendance = Attendance::where('user_id', $user->id)->first();
        $adminUser = User::where('role_id', config('constants.ROLE_ADMIN'))->first();
        $this->actingAs($adminUser);
        $this->get("/attendance/{$attendance->id}")->assertStatus(200);

        // 不適切な休憩開始データの送信およびレスポンスの確認
        $postData = [
            'attendance_id' => $attendance->id,
            'updated_at'    => $attendance->updated_at->format('Y-m-d H:i:s'),
            'clock_in'      => $attendance->clock_in,
            'clock_out'     => $attendance->clock_out,
            'note'          => '', // 備考欄を未入力
            'breakTimes'    => [], // 休憩情報なし
        ];
        $response = $this->from("/attendance/{$attendance->id}")
                         ->post('/attendance/stamp_correction_request', $postData);
        $response->assertRedirect("/attendance/{$attendance->id}");
        $response->assertSessionHasErrors([
            'note' => '備考を記入してください',
        ]);
    }
}
