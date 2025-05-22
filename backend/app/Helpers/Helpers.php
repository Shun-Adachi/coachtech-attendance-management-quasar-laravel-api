<?php

use Carbon\Carbon;
use App\Models\BreakTime;

if (!function_exists('calculateTotalBreakMinutes')) {
    /**
     * 指定された勤怠IDの休憩時間合計（分）を計算する
     *
     * @param  int  $attendanceId
     * @return int
     */
    function calculateTotalBreakMinutes($attendanceId)
    {
        return BreakTime::where('attendance_id', $attendanceId)
            ->whereNotNull('break_in')
            ->whereNotNull('break_out')
            ->get()
            ->sum(function ($break) {
                $breakIn = Carbon::parse($break->break_in)->setSecond(0);
                $breakOut = Carbon::parse($break->break_out)->setSecond(0);
                return $breakIn->diffInMinutes($breakOut);
            });
    }
}

if (!function_exists('formatTotalBreakTime')) {
    /**
     * 休憩時間（分）を "H:i" 形式にフォーマットして返す
     *
     * @param  int  $totalBreakMinutes
     * @return string
     */
    function formatTotalBreakTime($totalBreakMinutes)
    {
        $time = Carbon::createFromTimestampUTC($totalBreakMinutes * 60);
        return $time->format('H:i');
    }
}

if (!function_exists('calculateFormattedTotalWorkTime')) {
    /**
     * 出勤・退勤時間と休憩時間を元に勤務時間の合計を "H:i" 形式で返す
     *
     * @param  string $clockIn   出勤時刻（"H:i" 形式）
     * @param  string $clockOut  退勤時刻（"H:i" 形式）
     * @param  int    $totalBreakMinutes 休憩時間の合計（分）
     * @return string
     */
    function calculateFormattedTotalWorkTime($clockIn, $clockOut, $totalBreakMinutes)
    {
        // 退勤時刻がない場合は空文字を返す
        if (!$clockOut) {
            return '';
        }
        // 出勤・退勤時刻をCarbonオブジェクトに変換し、秒を0に設定
        $clockInTime = Carbon::parse($clockIn)->setSecond(0);
        $clockOutTime = Carbon::parse($clockOut)->setSecond(0);
        $workMinutes = $clockInTime->diffInMinutes($clockOutTime);
        $workMinutes -= $totalBreakMinutes;
        return gmdate('H:i', max($workMinutes * 60, 0));
    }
}

if (!function_exists('createCalendarDays')) {
    /**
     * 指定された月の開始日～終了日までの日付リストを作成するヘルパー関数
     * 各日付に対応する勤怠データ（あれば）を付加して配列として返します。
     *
     * @param  \Carbon\Carbon  $startOfMonth  月初日
     * @param  \Carbon\Carbon  $endOfMonth    月末日
     * @param  \Illuminate\Support\Collection  $attendances  勤怠データのコレクション
     * @return array  キーが "Y-m-d" 形式の日付、値が対応する勤怠データ（存在しない場合は null）
     */
    function createCalendarDays(Carbon $startOfMonth, Carbon $endOfMonth, $attendances)
    {
        $days = [];
        for ($date = $startOfMonth->copy(); $date->lte($endOfMonth); $date->addDay()) {
            $formattedDate = $date->format('Y-m-d');
            $attendance = $attendances->firstWhere('attendance_at', $formattedDate);

            // 勤怠データが存在する場合、フォーマット処理を実施
            if ($attendance) {
                $attendance->formatted_clock_in = $attendance->clock_in
                    ? Carbon::parse($attendance->clock_in)->format('H:i')
                    : '';
                $attendance->formatted_clock_out = $attendance->clock_out
                    ? Carbon::parse($attendance->clock_out)->format('H:i')
                    : '';
                $totalBreak = calculateTotalBreakMinutes($attendance->id);
                $attendance->total_break = $totalBreak;
                $attendance->formatted_total_break = formatTotalBreakTime($totalBreak);
                $attendance->formatted_total_work = calculateFormattedTotalWorkTime(
                    $attendance->clock_in,
                    $attendance->clock_out,
                    $totalBreak
                );
            }
            $days[$formattedDate] = $attendance;
        }
        return $days;
    }
}