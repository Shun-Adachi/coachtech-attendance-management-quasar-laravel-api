<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use App\Models\User;
use App\Models\Attendance;
use App\Models\BreakTime;
use Carbon\Carbon;
use Tests\TestCase;

class AdminStaffListTest extends TestCase
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
     * 管理者ユーザーが全一般ユーザーの「氏名」と「メールアドレス」を確認できることを検証するテスト
     */
    public function test_admin_can_see_all_general_users_staff_info()
    {
        $users = User::where('role_id', config('constants.ROLE_USER'))->get();
        $adminUser = User::where('role_id', config('constants.ROLE_ADMIN'))->first();
        $this->actingAs($adminUser);
        $response = $this->get(route('admin.staff.list'));
        $response->assertStatus(200);
        foreach($users as $user){
            $response->assertSee($user->name);
            $response->assertSee($user->email);
        }
    }

    /**
     * 勤怠一覧情報取得機能（管理者）
     * 選択した一般ユーザーの勤怠情報が正しく表示されることを検証するテスト
     */
    public function test_admin_can_see_staff_attendance_information()
    {
        // ユーザー情報および今日の日付を取得
        $user = User::where('role_id', config('constants.ROLE_USER'))->first();
        $targetDate = Carbon::today()->startOfMonth();
        $year = $targetDate->year;
        $month = $targetDate->month;
        $endOfMonth = $targetDate->copy()->endOfMonth();

        // テストデータの準備
        $expectedData = [];
        for ($day = 1; $day <= $endOfMonth->day; $day++) {
            $date = Carbon::create($year, $month, $day);
            // ばらつきを加えるため、各日のオフセット（分）
            $inOffset = $day % 10;         // 0～9分の加算
            $outOffset = $day % 5;         // 0～4分の減算
            $breakInOffset = $day % 3;       // 0～2分の加算
            $breakOutOffset = $day % 3;      // 0～2分の減算

            $clockIn = Carbon::createFromFormat('Y-m-d H:i', $date->format('Y-m-d') . ' 09:00')
                        ->addMinutes($inOffset);
            $clockOut = Carbon::createFromFormat('Y-m-d H:i', $date->format('Y-m-d') . ' 18:00')
                        ->subMinutes($outOffset);
            $breakIn = Carbon::createFromFormat('Y-m-d H:i', $date->format('Y-m-d') . ' 12:00')
                        ->addMinutes($breakInOffset);
            $breakOut = Carbon::createFromFormat('Y-m-d H:i', $date->format('Y-m-d') . ' 12:30')
                        ->subMinutes($breakOutOffset);

            // 勤怠レコードの作成
            $attendance = Attendance::create([
                'user_id'       => $user->id,
                'attendance_at' => $date->format('Y-m-d'),
                'status_id'     => config('constants.STATUS_WORKING'),
                'clock_in'      => $clockIn->format('H:i'),
                'clock_out'     => $clockOut->format('H:i'),
            ]);

            // 休憩レコードの作成（各日1件）
            BreakTime::create([
                'attendance_id' => $attendance->id,
                'break_in'      => $breakIn->format('H:i'),
                'break_out'     => $breakOut->format('H:i'),
            ]);

            // 期待される各値の計算
            $formattedDate = $date->format('m/d');
            $formattedClockIn = $clockIn->format('H:i');
            $formattedClockOut = $clockOut->format('H:i');

            // 休憩合計（分）
            $totalBreakMinutes = $breakIn->diffInMinutes($breakOut);
            $formattedTotalBreak = gmdate('H:i', $totalBreakMinutes * 60);

            // 勤務時間（分）＝ (退勤 - 出勤) - (休憩)
            $workMinutes = $clockIn->diffInMinutes($clockOut) - $totalBreakMinutes;
            $formattedTotalWork = gmdate('H:i', $workMinutes * 60);

            $expectedData[] = [
                'date' => $formattedDate,
                'clock_in' => $formattedClockIn,
                'clock_out' => $formattedClockOut,
                'total_break' => $formattedTotalBreak,
                'total_work' => $formattedTotalWork,
            ];
        }

        // スタッフ別一覧ページを表示
        $adminUser = User::where('role_id', config('constants.ROLE_ADMIN'))->first();
        $this->actingAs($adminUser);
        $url = "/admin/attendance/staff/{$user->id}";
        $response = $this->get($url);
        $response->assertStatus(200);
        $response->assertSee($user->name);

        // 1か月分の各日の期待値が全て表示されているかを検証
        foreach ($expectedData as $data) {
            $response->assertSee($data['date']);
            $response->assertSee($data['clock_in']);
            $response->assertSee($data['clock_out']);
            $response->assertSee($data['total_break']);
            $response->assertSee($data['total_work']);
        }
    }

    /**
     * 勤怠一覧情報取得機能（管理者）
     * 「前月」を押下した時に表示月の前月の情報が表示されることを検証するテスト
     */
    public function test_admin_can_see_previous_month_attendance_when_previous_month_button_is_pressed()
    {
        // テストデータの準備
        $lastMonth = Carbon::today()->subMonth();
        $startOfLastMonth = $lastMonth->copy()->startOfMonth();
        $endOfLastMonth = $lastMonth->copy()->endOfMonth();
        $year = $startOfLastMonth->year;
        $month = $startOfLastMonth->month;
        $user = User::where('role_id', config('constants.ROLE_USER'))->first();
        $attendances = Attendance::where('user_id', $user->id)
            ->whereBetween('attendance_at', [$startOfLastMonth->format('Y-m-d'), $endOfLastMonth->format('Y-m-d')])
            ->get();
        $adminUser = User::where('role_id', config('constants.ROLE_ADMIN'))->first();
        $this->actingAs($adminUser);

        // スタッフ別勤怠一覧画面表示
        $url = "/admin/attendance/staff/{$user->id}";
        $urlPrev = "/admin/attendance/staff/{$user->id}/{$year}/{$month}";
        $response = $this->get($url);
        $response->assertStatus(200);
        $response->assertSee($urlPrev);

        // 前月を押した後の確認
        $responsePrev = $this->get($urlPrev);
        $responsePrev->assertStatus(200);
        foreach($attendances as $attendance){
            $responsePrev->assertSee(Carbon::parse($attendance->attendance_at)->format('m/d'));
            $responsePrev->assertSee(Carbon::parse($attendance->clock_in)->format('H:i'));
            $responsePrev->assertSee(Carbon::parse($attendance->clock_out)->format('H:i'));
        }
    }

    /**
     * 勤怠一覧情報取得機能（管理者）
     * 「翌月」を押下した時に表示月の翌月の情報が表示されることを検証するテスト
     */
    public function test_admin_can_see_next_month_attendance_when_previous_month_button_is_pressed()
    {
        // テストデータの準備
        $twoMonthAgo = Carbon::today()->subMonth(2);
        $twoMonthAgoYear = $twoMonthAgo->year;
        $twoMonthAgoMonth = $twoMonthAgo->month;

        $nextMonth = $twoMonthAgo->copy()->addMonth();
        $startOfNextMonth = $nextMonth->copy()->startOfMonth();
        $endOfNextMonth = $nextMonth->copy()->endOfMonth();
        $year = $startOfNextMonth->year;
        $month = $startOfNextMonth->month;

        $user = User::where('role_id', config('constants.ROLE_USER'))->first();
        $attendances = Attendance::where('user_id', $user->id)
            ->whereBetween('attendance_at', [$startOfNextMonth->format('Y-m-d'), $endOfNextMonth->format('Y-m-d')])
            ->get();
        $adminUser = User::where('role_id', config('constants.ROLE_ADMIN'))->first();
        $this->actingAs($adminUser);

        // スタッフ別勤怠一覧画面表示(2か月前のURLからその翌月に移動)
        $urlTwoMonthAgo = "/admin/attendance/staff/{$user->id}/{$twoMonthAgoYear}/{$twoMonthAgoMonth}";
        $urlNext = "/admin/attendance/staff/{$user->id}/{$year}/{$month}";
        $response = $this->get($urlTwoMonthAgo);
        $response->assertStatus(200);
        $response->assertSee($urlNext);

        // 翌月を押した後の確認
        $responseNext = $this->get($urlNext);
        $responseNext->assertStatus(200);
        foreach($attendances as $attendance){
            $responseNext->assertSee(Carbon::parse($attendance->attendance_at)->format('m/d'));
            $responseNext->assertSee(Carbon::parse($attendance->clock_in)->format('H:i'));
            $responseNext->assertSee(Carbon::parse($attendance->clock_out)->format('H:i'));
        }
    }
}
