<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use App\Models\User;
use App\Models\Attendance;
use App\Models\BreakTime;
use Carbon\Carbon;
use Tests\TestCase;

class StampCorrectionRequestForAdminTest extends TestCase
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
     * 勤怠情報修正機能（管理者）
     * 「承認待ち」の修正申請が全て表示されることを検証するテスト
     */
    public function test_admin_can_see_all_pending_correction_requests()
    {
        // テストデータの準備
        $users = User::where('role_id', config('constants.ROLE_USER'))->get();
        $today = Carbon::today();
        $requestedAt1 = Carbon::now();
        $requestedAt2 = Carbon::now()->addMinutes(5);
        $attendance1 = Attendance::create([
            'user_id'       => $users[0]->id,
            'attendance_at' => $today->format('Y-m-d'),
            'status_id'     => config('constants.STATUS_PENDING'),
            'clock_in'      => '09:00:00',
            'clock_out'     => '18:00:00',
            'requested_at'  => $requestedAt1->format('Y-m-d H:i'),
            'note'          => '修正申請: User One',
        ]);
        $attendance2 = Attendance::create([
            'user_id'       => $users[1]->id,
            'attendance_at' => $today->format('Y-m-d'),
            'status_id'     => config('constants.STATUS_PENDING'),
            'clock_in'      => '08:50:00',
            'clock_out'     => '17:50:00',
            'requested_at'  => $requestedAt2->format('Y-m-d H:i'),
            'note'          => '修正申請: User Two',
        ]);

        // 管理者として承認待ち一覧画面を表示
        $adminUser = User::where('role_id', config('constants.ROLE_ADMIN'))->first();
        $this->actingAs($adminUser);
        $response = $this->get('/stamp_correction_request/list');
        $response->assertStatus(200);
        $response->assertSee($users[0]->name);
        $response->assertSee('修正申請: User One');
        $response->assertSee(Carbon::parse($attendance1->attendance_at)->format('Y/m/d'));
        $response->assertSee(Carbon::parse($attendance1->requested_at)->format('Y/m/d'));
        $response->assertSee($users[1]->name);
        $response->assertSee('修正申請: User Two');
        $response->assertSee(Carbon::parse($attendance2->attendance_at)->format('Y/m/d'));
        $response->assertSee(Carbon::parse($attendance2->requested_at)->format('Y/m/d'));
    }

    /**
     * 勤怠情報修正機能（管理者）
     * 承認済みの修正申請が全て表示されることを検証するテスト
     */
    public function test_admin_can_see_all_approved_correction_requests()
    {
        // テストデータの準備
        $users = User::where('role_id', config('constants.ROLE_USER'))->get();
        $today = Carbon::today();
        $requestedAt1 = Carbon::now();
        $requestedAt2 = Carbon::now()->addMinutes(5);
        $attendance1 = Attendance::create([
            'user_id'       => $users[0]->id,
            'attendance_at' => $today->format('Y-m-d'),
            'status_id'     => config('constants.STATUS_APPROVED'),
            'clock_in'      => '09:00:00',
            'clock_out'     => '18:00:00',
            'requested_at'  => $requestedAt1->format('Y-m-d H:i'),
            'note'          => '修正申請: User One',
        ]);
        $attendance2 = Attendance::create([
            'user_id'       => $users[1]->id,
            'attendance_at' => $today->format('Y-m-d'),
            'status_id'     => config('constants.STATUS_APPROVED'),
            'clock_in'      => '08:50:00',
            'clock_out'     => '17:50:00',
            'requested_at'  => $requestedAt2->format('Y-m-d H:i'),
            'note'          => '修正申請: User Two',
        ]);

        // 管理者として承認待ち一覧画面を表示
        $adminUser = User::where('role_id', config('constants.ROLE_ADMIN'))->first();
        $this->actingAs($adminUser);
        $response = $this->get('/stamp_correction_request/list?tab=approved');
        $response->assertStatus(200);
        $response->assertSee($users[0]->name);
        $response->assertSee('修正申請: User One');
        $response->assertSee(Carbon::parse($attendance1->attendance_at)->format('Y/m/d'));
        $response->assertSee(Carbon::parse($attendance1->requested_at)->format('Y/m/d'));
        $response->assertSee($users[1]->name);
        $response->assertSee('修正申請: User Two');
        $response->assertSee(Carbon::parse($attendance2->attendance_at)->format('Y/m/d'));
        $response->assertSee(Carbon::parse($attendance2->requested_at)->format('Y/m/d'));
    }

    /**
     * 修正申請詳細画面が正しい勤怠情報を表示することを検証するテスト
     */
    public function test_stamp_correction_approval_detail_displays_correct_data()
    {
        // テストデータの準備
        $user = User::where('role_id', config('constants.ROLE_USER'))->first();
        $today = Carbon::today();
        $attendance = Attendance::create([
            'user_id'       => $user->id,
            'attendance_at' => $today->format('Y-m-d'),
            'status_id'     => config('constants.STATUS_PENDING'),
            'clock_in'      => '09:00:00',
            'clock_out'     => '18:00:00',
        ]);
        $break1 = BreakTime::create([
            'attendance_id' => $attendance->id,
            'break_in'      => '12:00:00',
            'break_out'     => '12:30:00',
        ]);
        $break2 = BreakTime::create([
            'attendance_id' => $attendance->id,
            'break_in'      => '15:00:00',
            'break_out'     => '15:20:00',
        ]);

        // 修正申請詳細画面の確認
        $adminUser = User::where('role_id', config('constants.ROLE_ADMIN'))->first();
        $this->actingAs($adminUser);
        $url = route('stamp_correction_request.approve.show', ['attendance_id' => $attendance->id]);
        $response = $this->get($url);
        $response->assertStatus(200);
        $formattedYear = Carbon::parse($attendance->attendance_at)->format('Y年');
        $formattedDate = Carbon::parse($attendance->attendance_at)->format('n月j日');
        $formattedClockIn = Carbon::parse($attendance->clock_in)->format('H:i');
        $formattedClockOut = Carbon::parse($attendance->clock_out)->format('H:i');
        $formattedBreakIn1 = Carbon::parse($break1->break_in)->format('H:i');
        $formattedBreakOut1 = Carbon::parse($break1->break_out)->format('H:i');
        $formattedBreakIn2 = Carbon::parse($break2->break_in)->format('H:i');
        $formattedBreakOut2 = Carbon::parse($break1->break_out)->format('H:i');
        $response->assertSee($user->name);
        $response->assertSee($formattedYear);
        $response->assertSee($formattedDate);
        $response->assertSee($formattedClockIn);
        $response->assertSee($formattedClockOut);
        $response->assertSee($formattedBreakIn1);
        $response->assertSee($formattedBreakOut1);
        $response->assertSee($formattedBreakIn2);
        $response->assertSee($formattedBreakOut2);
    }

    /**
     * 勤怠情報修正機能（管理者）
     * 修正申請の承認処理が正しく行われ、勤怠情報が更新されることを検証するテスト
     */
    public function test_admin_approval_of_correction_request_updates_attendance_record()
    {
        // テストデータの準備
        $this->withoutMiddleware(\App\Http\Middleware\VerifyCsrfToken::class);
        $approvedStatus = config('constants.STATUS_APPROVED');
        $user = User::where('role_id', config('constants.ROLE_USER'))->first();
        $today = Carbon::today();
        $attendance = Attendance::create([
            'user_id'       => $user->id,
            'attendance_at' => $today->format('Y-m-d'),
            'status_id'     => config('constants.STATUS_PENDING'),
            'clock_in'      => '09:00:00',
            'clock_out'     => '18:00:00',
            'requested_at'  => $today->format('Y-m-d'),
            'note'          => 'テストデータ',
        ]);
        // 管理者ログイン後、修正申請を承認
        $adminUser = User::where('role_id', config('constants.ROLE_ADMIN'))->first();
        $this->actingAs($adminUser);
        $approvalUrl = "/stamp_correction_request/approve/{$attendance->id}";
        $response = $this->post($approvalUrl);
        $attendance->refresh();
        $this->assertEquals($approvedStatus, $attendance->status_id, 'Attendanceのステータスが「承認済み」に更新されていません。');
        $response->assertStatus(302);
    }
}
