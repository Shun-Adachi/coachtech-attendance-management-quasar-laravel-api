<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use App\Models\User;
use App\Models\Status;
use App\Models\Attendance;
use Carbon\Carbon;
use Tests\TestCase;

class StampCorrectionRequestForUserTest extends TestCase
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
     * 勤怠詳細情報修正機能（一般ユーザー）
     * 出勤時間が退勤時間より後になっている場合、エラーメッセージが表示されることを検証するテスト
     */
    public function test_invalid_clock_times_show_validation_error()
    {
        // テストデータの準備
        $this->withoutMiddleware(\App\Http\Middleware\VerifyCsrfToken::class);
        $user = User::where('role_id', config('constants.ROLE_USER'))->first();
        $today = Carbon::today();
        $attendance = Attendance::create([
            'user_id'       => $user->id,
            'status_id'     => config('constants.STATUS_WORKING'),
            'attendance_at' => $today,
            'clock_in'      => '09:00:00',
            'clock_out'     => '18:00:00',
            'updated_at'    => Carbon::now(),
        ]);
        $this->actingAs($user);
        $this->get("/attendance/{$attendance->id}")->assertStatus(200);

        // 修正データを送信
        $response = $this->from("/attendance/{$attendance->id}")
            ->post('/attendance/stamp_correction_request', [
                'year'          => $today->format('Y年'),
                'date'          => $today->format('n月j日'),
                'attendance_id' => $attendance->id,
                'clock_in'      => '19:00',
                'clock_out'     => '18:00',
                'note'          => '電車遅延のため',
                'updated_at'    => $attendance->updated_at,
            ]);
        $response->assertRedirect("/attendance/{$attendance->id}");
        $response->assertSessionHasErrors([
            'clock_in' => '出勤時間もしくは退勤時間が不適切な値です',
        ]);
    }

    /**
     * 勤怠詳細情報修正機能（一般ユーザー）
     * 休憩開始時間が退勤時間より後になっている場合、エラーメッセージが表示されることを検証するテスト
     */
    public function test_invalid_break_in_times_show_validation_error()
    {
        // テストデータの準備
        $this->withoutMiddleware(\App\Http\Middleware\VerifyCsrfToken::class);
        $user = User::where('role_id', config('constants.ROLE_USER'))->first();
        $today = Carbon::today();
        $attendance = Attendance::create([
            'user_id'       => $user->id,
            'status_id'     => config('constants.STATUS_WORKING'),
            'attendance_at' => $today,
            'clock_in'      => '09:00',
            'clock_out'     => '18:00',
            'updated_at'    => Carbon::now(),
        ]);
        $this->actingAs($user);
        $this->get("/attendance/{$attendance->id}")->assertStatus(200);

        // 修正データを送信
        $response = $this->from("/attendance/{$attendance->id}")
            ->post('/attendance/stamp_correction_request', [
                'year'          => $today->format('Y年'),
                'date'          => $today->format('n月j日'),
                'attendance_id' => $attendance->id,
                'clock_in'      => '10:00',
                'clock_out'     => '18:00',
                'breakTimes'    => [
                    'new' => [
                        'break_in'  => '19:00',
                        'break_out' => '11:00',
                    ],
                ],
                'note'          => '電車遅延のため',
                'updated_at'    => $attendance->updated_at,
            ]);
        $response->assertRedirect("/attendance/{$attendance->id}");
        $response->assertSessionHasErrors([
            'clock_in' => '出勤時間もしくは退勤時間が不適切な値です',
        ]);
    }

    /**
     * 勤怠詳細情報修正機能（一般ユーザー）
     * 休憩終了時間が退勤時間より後になっている場合、エラーメッセージが表示されることを検証するテスト
     */
    public function test_invalid_break_out_times_show_validation_error()
    {
        // テストデータの準備
        $this->withoutMiddleware(\App\Http\Middleware\VerifyCsrfToken::class);
        $user = User::where('role_id', config('constants.ROLE_USER'))->first();
        $today = Carbon::today();
        $attendance = Attendance::create([
            'user_id'       => $user->id,
            'status_id'     => config('constants.STATUS_WORKING'),
            'attendance_at' => $today,
            'clock_in'      => '09:00',
            'clock_out'     => '18:00',
            'updated_at'    => Carbon::now(),
        ]);
        $this->actingAs($user);
        $this->get("/attendance/{$attendance->id}")->assertStatus(200);

        // 修正データを送信
        $response = $this->from("/attendance/{$attendance->id}")
            ->post('/attendance/stamp_correction_request', [
                'year'          => $today->format('Y年'),
                'date'          => $today->format('n月j日'),
                'attendance_id' => $attendance->id,
                'clock_in'      => '10:00',
                'clock_out'     => '18:00',
                'breakTimes'    => [
                    'new' => [
                        'break_in'  => '12:00',
                        'break_out' => '19:00',
                    ],
                ],
                'note'          => '電車遅延のため',
                'updated_at'    => $attendance->updated_at,
            ]);
        $response->assertRedirect("/attendance/{$attendance->id}");
        $response->assertSessionHasErrors([
            'clock_in' => '出勤時間もしくは退勤時間が不適切な値です',
        ]);
    }

    /**
     * 備考欄が未入力の場合、エラーメッセージが表示されることを検証するテスト
     */
    public function test_note_field_validation_when_empty()
    {
        // テストデータの準備
        $this->withoutMiddleware(\App\Http\Middleware\VerifyCsrfToken::class);
        $user = User::where('role_id', config('constants.ROLE_USER'))->first();
        $today = Carbon::today();
        $attendance = Attendance::create([
            'user_id'       => $user->id,
            'status_id'     => config('constants.STATUS_WORKING'),
            'attendance_at' => $today,
            'clock_in'      => '09:00',
            'clock_out'     => '18:00',
            'updated_at'    => Carbon::now(),
        ]);
        $this->actingAs($user);
        $this->get("/attendance/{$attendance->id}")->assertStatus(200);

        // 修正データを送信
        $response = $this->from("/attendance/{$attendance->id}")
            ->post('/attendance/stamp_correction_request', [
                'year'          => $today->format('Y年'),
                'date'          => $today->format('n月j日'),
                'attendance_id' => $attendance->id,
                'clock_in'      => '10:00',
                'clock_out'     => '18:00',
                'breakTimes'    => [
                    'new' => [
                        'break_in'  => '12:00',
                        'break_out' => '13:00',
                    ],
                ],
                'note'          => '',
                'updated_at'    => $attendance->updated_at,
            ]);
        $response->assertRedirect("/attendance/{$attendance->id}");
        $response->assertSessionHasErrors([
            'note' => '備考を記入してください',
        ]);
    }

    /**
     * 勤怠詳細情報修正機能（一般ユーザー）
     * 修正申請処理が実行され、管理者の承認画面と申請一覧画面に表示されることを検証するテスト
     */
    public function test_correction_request_is_submitted_and_visible_in_admin_views()
    {
        // テストデータの準備
        $this->withoutMiddleware(\App\Http\Middleware\VerifyCsrfToken::class);
        $user = User::where('role_id', config('constants.ROLE_USER'))->first();
        $today = Carbon::today();
        $attendance = Attendance::create([
            'user_id'       => $user->id,
            'status_id'     => config('constants.STATUS_WORKING'),
            'attendance_at' => $today,
            'clock_in'      => '09:00',
            'clock_out'     => '18:00',
            'updated_at'    => Carbon::now(),
        ]);
        $this->actingAs($user);
        $this->get("/attendance/{$attendance->id}")->assertStatus(200);

        // 修正データを送信
        $response = $this->from("/attendance/{$attendance->id}")
            ->post('/attendance/stamp_correction_request', [
                'year'          => $today->format('Y年'),
                'date'          => $today->format('n月j日'),
                'attendance_id' => $attendance->id,
                'clock_in'      => '10:00',
                'clock_out'     => '19:00',
                'breakTimes'    => [
                    'new' => [
                        'break_in'  => '12:00',
                        'break_out' => '13:00',
                    ],
                ],
                'note'          => '電車遅延のため',
                'updated_at'    => $attendance->updated_at,
            ]);
        $response->assertStatus(200);
        $attendance->refresh();
        $pendingStatusId = Status::where('name', '承認待ち')->value('id');
        $this->assertEquals($pendingStatusId, $attendance->status_id, 'Attendanceのステータスが「承認待ち」に更新されていません。');
        $this->assertNotNull($attendance->requested_at, '修正申請日時が記録されていません。');

        // 管理者で勤怠承認画面を確認
        $adminUser = User::where('role_id', config('constants.ROLE_ADMIN'))->first();
        $this->actingAs($adminUser);
        $approvalResponse = $this->get("/stamp_correction_request/approve/{$attendance->id}");
        $approvalResponse->assertStatus(200);
        $approvalResponse->assertSee($user->name);
        $approvalResponse->assertSee('電車遅延のため');
        $approvalResponse->assertSee(Carbon::parse($attendance->requested_at)->format('Y年'));
        $approvalResponse->assertSee(Carbon::parse($attendance->requested_at)->format('n月j日'));

        // 管理者で申請一覧画面を確認
        $listResponse = $this->get('/stamp_correction_request/list');
        $listResponse->assertStatus(200);
        $listResponse->assertSee($user->name);
        $listResponse->assertSee(Carbon::parse($attendance->requested_at)->format('Y/m/d'));
    }

    /**
     * 勤怠詳細情報修正機能（一般ユーザー）
     * 修正申請処理が実行され、管理者の承認画面と申請一覧画面に表示されることを検証するテスト
     */
    public function test_correction_request_is_submitted_and_visible_in_user_views()
    {
        // テストデータの準備
        $this->withoutMiddleware(\App\Http\Middleware\VerifyCsrfToken::class);
        $user = User::where('role_id', config('constants.ROLE_USER'))->first();
        $today = Carbon::today();
        $attendance = Attendance::create([
            'user_id'       => $user->id,
            'status_id'     => config('constants.STATUS_WORKING'),
            'attendance_at' => $today,
            'clock_in'      => '09:00',
            'clock_out'     => '18:00',
            'updated_at'    => Carbon::now(),
        ]);
        $this->actingAs($user);
        $this->get("/attendance/{$attendance->id}")->assertStatus(200);

        // 修正データを送信
        $response = $this->from("/attendance/{$attendance->id}")
            ->post('/attendance/stamp_correction_request', [
                'year'          => $today->format('Y年'),
                'date'          => $today->format('n月j日'),
                'attendance_id' => $attendance->id,
                'clock_in'      => '10:00',
                'clock_out'     => '19:00',
                'breakTimes'    => [
                    'new' => [
                        'break_in'  => '12:00',
                        'break_out' => '13:00',
                    ],
                ],
                'note'          => '電車遅延のため',
                'updated_at'    => $attendance->updated_at,
            ]);
        $response->assertStatus(200);
        $attendance->refresh();
        $pendingStatusId = Status::where('name', '承認待ち')->value('id');
        $this->assertEquals($pendingStatusId, $attendance->status_id, 'Attendanceのステータスが「承認待ち」に更新されていません。');
        $this->assertNotNull($attendance->requested_at, '修正申請日時が記録されていません。');

        // 申請一覧画面を確認
        $listResponse = $this->get('/stamp_correction_request/list');
        $listResponse->assertStatus(200);
        $listResponse->assertSee($user->name);
        $listResponse->assertSee(Carbon::parse($attendance->requested_at)->format('Y/m/d'));
    }

    /**
     * 勤怠詳細情報修正機能（一般ユーザー）
     * 「承認済み」に管理者が承認した修正申請が全て表示されることを検証するテスト
     */
    public function test_correction_request_is_approval_in_user_view()
    {
        // テストデータの準備
        $this->withoutMiddleware(\App\Http\Middleware\VerifyCsrfToken::class);
        $user = User::where('role_id', config('constants.ROLE_USER'))->first();
        $workingStatusId = Status::where('name', '勤務中')->value('id');
        $approvedStatusId = Status::where('name', '承認済み')->value('id');
        $today = Carbon::today();

        // Attendanceレコード1：当日
        $attendance1 = Attendance::create([
            'user_id'       => $user->id,
            'attendance_at' => $today->format('Y-m-d'),
            'status_id'     => $workingStatusId,
            'clock_in'      => '09:00',
            'clock_out'     => '18:00',
            'updated_at'    => Carbon::now(),
        ]);

        // Attendanceレコード2：翌日（別日付）
        $attendance2 = Attendance::create([
            'user_id'       => $user->id,
            'attendance_at' => $today->copy()->addDay()->format('Y-m-d'),
            'status_id'     => $workingStatusId,
            'clock_in'      => '09:00',
            'clock_out'     => '18:00',
            'updated_at'    => Carbon::now(),
        ]);
        $this->actingAs($user);

        // 修正申請データ for attendance1
        $updateData1 = [
            'attendance_id' => $attendance1->id,
            'updated_at'    => $attendance1->updated_at->format('Y-m-d H:i:s'),
            'year'          => $today->format('Y年'),
            'date'          => $today->format('n月j日'),
            'clock_in'      => '09:15',
            'clock_out'     => '18:00',
            'note'          => '修正申請1',
                'breakTimes'    => [
                    'new' => [
                        'break_in'  => '12:00',
                        'break_out' => '13:00',
                    ],
                ],
        ];

        // 修正申請データ for attendance2
        $updateData2 = [
            'attendance_id' => $attendance2->id,
            'updated_at'    => $attendance2->updated_at->format('Y-m-d H:i:s'),
            'year'          => $today->copy()->addDay()->format('Y年'),
            'date'          => $today->copy()->addDay()->format('n月j日'),
            'clock_in'      => '09:20',
            'clock_out'     => '18:00',
            'note'          => '修正申請2',
                'breakTimes'    => [
                    'new' => [
                        'break_in'  => '13:00',
                        'break_out' => '13:30',
                    ],
                ],
        ];

        // 一般ユーザーとしてそれぞれのAttendanceに対して修正申請を送信
        $response1 = $this->from("/attendance/{$attendance1->id}")
                          ->post('/attendance/stamp_correction_request', $updateData1);
        $response1->assertStatus(200);
        $response2 = $this->from("/attendance/{$attendance2->id}")
                          ->post('/attendance/stamp_correction_request', $updateData2);
        $response2->assertStatus(200);
        $attendance1->refresh();
        $attendance2->refresh();
        $this->assertNotNull($attendance1->requested_at, 'Attendance1の修正申請日時が記録されていません。');
        $this->assertNotNull($attendance2->requested_at, 'Attendance2の修正申請日時が記録されていません。');

        // 承認済みに変更
        $approvedNow = Carbon::now()->format('Y-m-d H:i:s');
        $attendance1->update([
            'status_id'    => $approvedStatusId,
            'requested_at' => $approvedNow,
        ]);
        $attendance2->update([
            'status_id'    => $approvedStatusId,
            'requested_at' => $approvedNow,
        ]);

        // 申請一覧画面(承認済み)の確認
        $listResponse = $this->get('/stamp_correction_request/list?tab=approved');
        $listResponse->assertStatus(200);
        $listResponse->assertSee($user->name);
        $listResponse->assertSee('修正申請1');
        $listResponse->assertSee('修正申請2');
        $listResponse->assertSee(Carbon::parse($attendance1->requested_at)->format('Y/m/d'));
        $listResponse->assertSee(Carbon::parse($attendance2->requested_at)->format('Y/m/d'));
    }

    /**
     * 勤怠詳細情報修正機能（一般ユーザー）
     * 「各申請の『詳細』ボタンを押下すると申請詳細画面に遷移する」ことを検証するテスト
     */
    public function test_detail_button_navigates_to_correction_request_detail_page()
    {
        // テストデータの準備
        $this->withoutMiddleware(\App\Http\Middleware\VerifyCsrfToken::class);
        $user = User::where('role_id', config('constants.ROLE_USER'))->first();
        $today = Carbon::today();
        $attendance = Attendance::create([
            'user_id'       => $user->id,
            'status_id'     => config('constants.STATUS_WORKING'),
            'attendance_at' => $today,
            'clock_in'      => '09:00',
            'clock_out'     => '18:00',
            'updated_at'    => Carbon::now(),
        ]);
        $this->actingAs($user);
        $this->get("/attendance/{$attendance->id}")->assertStatus(200);

        // 修正データを送信
        $response = $this->from("/attendance/{$attendance->id}")
            ->post('/attendance/stamp_correction_request', [
                'year'          => $today->format('Y年'),
                'date'          => $today->format('n月j日'),
                'attendance_id' => $attendance->id,
                'clock_in'      => '10:00',
                'clock_out'     => '19:00',
                'breakTimes'    => [
                    'new' => [
                        'break_in'  => '12:00',
                        'break_out' => '13:00',
                    ],
                ],
                'note'          => '電車遅延のため',
                'updated_at'    => $attendance->updated_at,
            ]);
        $response->assertStatus(200);
        $attendance->refresh();

        // 申請一覧画面を確認
        $listResponse = $this->get('/stamp_correction_request/list');
        $listResponse->assertStatus(200);
        $detailUrl = "/attendance/{$attendance->id}";
        $listResponse->assertSee($detailUrl);

        // 申請詳細画面を確認
        $detailResponse = $this->get($detailUrl);
        $detailResponse->assertStatus(200);
        $detailResponse->assertSee($user->name);
        $detailResponse->assertSee('電車遅延のため');
        $detailResponse->assertSee(Carbon::parse($attendance->requested_at)->format('Y年'));
        $detailResponse->assertSee(Carbon::parse($attendance->requested_at)->format('n月j日'));
    }
}
