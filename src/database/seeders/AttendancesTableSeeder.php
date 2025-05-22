<?php

namespace Database\Seeders;

use Carbon\Carbon;
use Illuminate\Database\Seeder;
use App\Models\Attendance;

class AttendancesTableSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        // 先月の日付を取得
        $lastMonth = Carbon::now()->subMonth();
        $startOfLastMonth = $lastMonth->copy()->startOfMonth();
        $endOfLastMonth   = $lastMonth->copy()->endOfMonth();

        $userIds = [2, 3];

        // 先月の各日について、各ユーザー分の勤怠データを作成
        for ($date = $startOfLastMonth->copy(); $date->lte($endOfLastMonth); $date->addDay()) {
            foreach ($userIds as $userId) {
                // 出勤・退勤時刻の例
                $clockIn  = Carbon::createFromFormat('Y-m-d H:i:s', $date->format('Y-m-d') . ' 09:00:00');
                $clockOut = Carbon::createFromFormat('Y-m-d H:i:s', $date->format('Y-m-d') . ' 18:00:00');

                Attendance::create([
                    'user_id'       => $userId,
                    'status_id'     => config('constants.STATUS_LEAVING'),
                    'attendance_at' => $date->format('Y-m-d'),
                    'requested_at'  => null,
                    'clock_in'      => $clockIn->format('H:i:s'),
                    'clock_out'     => $clockOut->format('H:i:s'),
                    'note'          => 'Seeder: 先月の勤怠データ',
                ]);
            }
        }
    }
}
