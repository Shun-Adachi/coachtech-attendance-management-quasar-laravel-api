<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Carbon\Carbon;
use App\Models\Attendance;
use App\Models\BreakTime;

class BreakTimesTableSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        // すべての勤怠レコードに対して休憩データを登録する例
        $attendances = Attendance::all();

        foreach ($attendances as $attendance) {
            // 勤怠日のフォーマット（Y-m-d）
            $attendanceDate = Carbon::parse($attendance->attendance_at)->format('Y-m-d');

            // 1件目の休憩時間：12:00～12:30
            $breakIn1  = Carbon::createFromFormat('Y-m-d H:i:s', $attendanceDate . ' 12:00:00');
            $breakOut1 = Carbon::createFromFormat('Y-m-d H:i:s', $attendanceDate . ' 12:30:00');

            BreakTime::create([
                'attendance_id' => $attendance->id,
                'break_in'      => $breakIn1->format('H:i:s'),
                'break_out'     => $breakOut1->format('H:i:s'),
            ]);

            // 2件目の休憩時間：15:00～15:15
            $breakIn2  = Carbon::createFromFormat('Y-m-d H:i:s', $attendanceDate . ' 15:00:00');
            $breakOut2 = Carbon::createFromFormat('Y-m-d H:i:s', $attendanceDate . ' 15:15:00');

            BreakTime::create([
                'attendance_id' => $attendance->id,
                'break_in'      => $breakIn2->format('H:i:s'),
                'break_out'     => $breakOut2->format('H:i:s'),
            ]);
        }
    }
}
