<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use App\Models\User;
use App\Models\Attendance;
use App\Models\Status;
use Carbon\Carbon;

class AttendanceListForUserTest extends TestCase
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
     * 勤怠一覧画面に自分が行った勤怠情報が全て表示されている
     */
    public function test_user_can_see_all_own_attendance_records()
    {
        // テストデータの準備
        $user = User::where('role_id', config('constants.ROLE_USER'))->first();
        $workingStatusId    = Status::where('name', '勤務中')->value('id');
        $today = Carbon::today();
        $attendanceData = [
            // 勤怠1: 8:00 - 17:00
            [
                'attendance_at' => $today->copy()->subDays(2),
                'clock_in'  => '08:00:00',
                'clock_out' => '17:00:00',
            ],
            // 勤怠2: 9:15 - 18:30
            [
                'attendance_at' => $today->copy()->subDays(1),
                'clock_in'  => '09:15:00',
                'clock_out' => '18:30:00',
            ],
            // 勤怠3: 10:00 - 19:00
            [
                'attendance_at' => $today->copy(),
                'clock_in'  => '10:00:00',
                'clock_out' => '19:00:00',
            ],
        ];
        foreach ($attendanceData as $data) {
            Attendance::create([
                'user_id'       => $user->id,
                'status_id'     => $workingStatusId,
                'attendance_at' => $data['attendance_at'],
                'clock_in'      => $data['clock_in'],
                'clock_out'     => $data['clock_out'],
            ]);
        }
        $this->actingAs($user);

        // 勤怠一覧画面の確認
        $response = $this->get('/attendance/list');
        $response->assertStatus(200);
        foreach ($attendanceData as $data) {
            $formattedDate = Carbon::parse($data['attendance_at'])->format('m/d');
            $response->assertSee($formattedDate);
            $clockInFormatted  = Carbon::parse($data['clock_in'])->format('H:i');
            $clockOutFormatted = Carbon::parse($data['clock_out'])->format('H:i');
            $response->assertSee($clockInFormatted);
            $response->assertSee($clockOutFormatted);
        }
    }

    /**
     * 勤怠一覧画面に遷移した際に現在の月が表示される
     */
    public function test_attendance_list_shows_current_month_for_user()
    {
        $user = User::where('role_id', config('constants.ROLE_USER'))->first();
        $this->actingAs($user);
        $response = $this->get('/attendance/list');
        $response->assertStatus(200);
        $currentMonth = Carbon::now()->format('Y/m');
        $response->assertSee($currentMonth);
    }

    /**
     * 「前月」を押下した時に表示月の前月の情報が表示される
     */
    public function test_user_can_see_previous_month_attendance()
    {
        $user = User::where('role_id', config('constants.ROLE_USER'))->first();
        $this->actingAs($user);
        $lastMonth = Carbon::now()->subMonth();
        $year  = $lastMonth->year;
        $month = $lastMonth->month;

        $response = $this->get("/attendance/list");
        $response->assertStatus(200);

        $response = $this->get("/attendance/list/{$year}/{$month}");
        $response->assertStatus(200);
        $response->assertSee('09:00');
        $response->assertSee('18:00');
        $someDay = $lastMonth->copy()->setDay(15);
        $response->assertSee($someDay->format('m/d'));
    }

    /**
     * 勤怠一覧情報取得機能（一般ユーザー）
     * 「翌月」を押下した時に表示月の翌月の情報が表示される
     */
    public function test_user_can_see_next_month_attendance()
    {
        // テストデータの準備
        $user = User::where('role_id', config('constants.ROLE_USER'))->first();
        $nextMonth = Carbon::now()->addMonth()->startOfMonth();
        $nextDates = [
            $nextMonth->copy()->setDay(5),
            $nextMonth->copy()->setDay(12),
        ];
        foreach ($nextDates as $date) {
            Attendance::create([
                'user_id'       => $user->id,
                'status_id'     => 2,
                'attendance_at' => $date->format('Y-m-d'),
                'clock_in'      => $date->copy()->setTime(8, 30)->format('H:i:s'),
                'clock_out'     => $date->copy()->setTime(17, 30)->format('H:i:s'),
            ]);
        }
        $this->actingAs($user);
        $responseCurrent = $this->get('/attendance/list');
        $responseCurrent->assertStatus(200);

        // 翌月データの確認
        $responseNext = $this->get("/attendance/list/{$nextMonth->year}/{$nextMonth->month}");
        $responseNext->assertStatus(200);
        foreach ($nextDates as $date) {
            $formattedDate = Carbon::parse($date)->format('m/d');
            $responseNext->assertSee($formattedDate);
        }
    }

    /**
     * 勤怠一覧情報取得機能（一般ユーザー）
     */
    public function test_attendance_detail_page_is_displayed_when_detail_button_is_pressed()
    {

        $user = User::where('role_id', config('constants.ROLE_USER'))->first();

        // 3. 当日のAttendanceレコードを作成
        $today = Carbon::today();
        $attendance = Attendance::create([
            'user_id'       => $user->id,
            'status_id'     => Status::where('name', '勤務中')->value('id'),
            'attendance_at' => $today,
            'clock_in'      => '09:00:00',
            'clock_out'     => '18:00:00',
        ]);

        // 勤怠一覧画面を表示
        $this->actingAs($user);
        $listResponse = $this->get('/attendance/list');
        $listResponse->assertStatus(200);

        // 勤怠一覧詳細画面の確認
        $detailUrl = "/attendance/{$attendance->id}";
        $listResponse->assertSee($detailUrl);
        $detailResponse = $this->get($detailUrl);
        $detailResponse->assertStatus(200);
        $expectedFormattedYear = Carbon::parse($attendance->attendance_at)->format('Y年');
        $detailResponse->assertSee($expectedFormattedYear);
        $expectedFormattedDate = Carbon::parse($attendance->attendance_at)->format('n月j日');
        $detailResponse->assertSee($expectedFormattedDate);
    }
}
