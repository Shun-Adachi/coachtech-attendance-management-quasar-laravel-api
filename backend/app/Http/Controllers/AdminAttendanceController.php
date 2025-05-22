<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Http\Requests\SubmitCorrectionRequest;
use App\Models\User;
use App\Models\Attendance;
use App\Models\BreakTime;
use Carbon\Carbon;
use Symfony\Component\HttpFoundation\StreamedResponse;

class AdminAttendanceController extends Controller
{
    // 勤怠一覧画面
    public function index(Request $request, $year = null, $month = null, $day = null)
    {
        $user = Auth::user();
        Carbon::setLocale('ja');
        $currentDate = $year && $month && $day ? Carbon::create($year, $month, $day) : Carbon::now();
        $prevDay = $currentDate->copy()->subDay();
        $nextDay = $currentDate->copy()->addDay();

        // 今日の勤怠データを取得
        $attendances = Attendance::whereDate('attendance_at', $currentDate->toDateString()) ->get();

        // 勤怠データのフォーマット
        foreach ($attendances as $attendance) {
            $attendance->formatted_clock_in = $attendance->clock_in
                ? Carbon::parse($attendance->clock_in)->format('H:i')
                : '';
            $attendance->formatted_clock_out = $attendance->clock_out
                ? Carbon::parse($attendance->clock_out)->format('H:i')
                : '';
            // 休憩時間の合計
            $totalBreak = calculateTotalBreakMinutes($attendance->id);
            $attendance->total_break = $totalBreak;
            $attendance->formatted_total_break = formatTotalBreakTime($totalBreak);
            // 勤務時間の合計
            $attendance->formatted_total_work = calculateFormattedTotalWorkTime($attendance->clock_in, $attendance->clock_out, $totalBreak);
        }
        return view('admin.attendance.index', compact('user',  'currentDate', 'attendances', 'prevDay', 'nextDay'));
    }

    // 勤怠詳細画面表示
    public function showAttendance(Request $request)
    {
        $user = Auth::user();
        $attendance = Attendance::with('user')->where('id',$request->attendance_id)->first();
        $attendance->formatted_year = Carbon::parse($attendance->attendance_at)->format('Y年');
        $attendance->formatted_date = Carbon::parse($attendance->attendance_at)->format('n月j日');
        $attendance->formatted_clock_in =$attendance->clock_in ? Carbon::parse($attendance->clock_in)->format('H:i') : null;
        $attendance->formatted_clock_out = $attendance->clock_out ? Carbon::parse($attendance->clock_out)->format('H:i') : null;
        $breakTimes = BreakTime::where('attendance_id',$request->attendance_id)->get();
        // 休憩データをフォーマット
        $breakTimes->transform(function ($break) {
            $break->break_in = $break->break_in ? Carbon::parse($break->break_in)->format('H:i') : null;
            $break->break_out = $break->break_out ? Carbon::parse($break->break_out)->format('H:i') : null;
            return $break;
        });
        $isPending = $attendance->status_id === config('constants.STATUS_PENDING');
        $isApproved = $attendance->status_id === config('constants.STATUS_APPROVED');
        return view('admin.attendance.show', compact('user', 'attendance', 'breakTimes','isPending', 'isApproved'));
    }

    // 修正処理
    public function submitCorrection(SubmitCorrectionRequest $request)
    {
        $validated = $request->validated();
        $attendance = Attendance::find($request->attendance_id);
        if (!$attendance) {
            return redirect()->back()->withErrors(['attendance' => '勤怠データが見つかりませんでした。']);
        }
        // 他のユーザーに更新されていないことの確認
        $clientUpdatedAt = Carbon::parse($validated['updated_at']);
        if ($attendance->updated_at->gt($clientUpdatedAt)) {
            return redirect()->back()->withErrors(['attendance' => '他のユーザーが修正処理を行ったので処理を中止しました。']);
        }

        // 入力されたyearとdateを結合してYYYY-MM-DD形式に変換する
        $yearStr = rtrim($validated['year'], '年');

        if (preg_match('/^(\d{1,2})月(\d{1,2})日$/', $validated['date'], $matches)) {
            $month = str_pad($matches[1], 2, '0', STR_PAD_LEFT);
            $day   = str_pad($matches[2], 2, '0', STR_PAD_LEFT);
        } else {
            return redirect()->back()->withErrors(['date' => '日付の形式が正しくありません。']);
        }
        $newAttendanceAt = $yearStr . '-' . $month . '-' . $day;
        // 変更後の日付が現在の attendance_at と異なる場合は、重複チェックを実施
        if ($attendance->attendance_at != $newAttendanceAt) {
            $existingAttendance = Attendance::where('attendance_at', $newAttendanceAt)
                ->where('user_id', $attendance->user_id)
                ->where('id', '<>', $attendance->id)
                ->first();
            if ($existingAttendance) {
                return redirect()->back()->withErrors(['attendance' => '変更後の日付はすでに存在します。']);
            }
            // 重複がなければ更新する
            $attendance->attendance_at = $newAttendanceAt;
        }

        // 出退勤時間の更新
        $attendance->clock_in  = $validated['clock_in'];
        $attendance->clock_out = $validated['clock_out'];
        $attendance->note = $validated['note'];
        $attendance->save();
        // 各休憩データの更新または新規追加
        foreach ($validated['breakTimes'] as $breakTimeId => $times) {
            // 休憩開始時間と休憩終了時間がどちらも空欄の場合は処理しない
            if (empty(trim($times['break_in'])) && empty(trim($times['break_out']))) {
                continue;
            }
            // 既存の休憩データの場合（IDが数値の場合）
            if (is_numeric($breakTimeId)) {
                $breakTime = BreakTime::find($breakTimeId);
                if ($breakTime) {
                    $breakTime->break_in  = $times['break_in'];
                    $breakTime->break_out = $times['break_out'];
                    $breakTime->save();
                }
            } else {
                // 休憩データを追加する場合
                 BreakTime::create([
                    'attendance_id' => $attendance->id,
                    'break_in'      => $times['break_in'],
                    'break_out'     => $times['break_out'],
            ]);
            }
        }
        return redirect()->back()->with('message', '勤怠を修正しました');
    }

    // スタッフ一覧画面
    public function showStaffIndex(Request $request)
    {
        $user = Auth::user();
        $staffList = User::where('role_id', config('constants.ROLE_USER'))->get();
        return view('admin.staff.index', compact('user', 'staffList'));
    }

    // スタッフ別勤怠一覧画面
    public function showStaffAttendance(Request $request, $user_id, $year = null, $month = null)
    {
        $user = Auth::user();
        $staff = User::where('id', $request->user_id)->first();
        Carbon::setLocale('ja');
        $currentDate = $year && $month ? Carbon::create($year, $month, 1) : Carbon::now();
        $prevMonth = $currentDate->copy()->subMonth();
        $nextMonth = $currentDate->copy()->addMonth();
        $startOfMonth = $currentDate->copy()->startOfMonth();
        $endOfMonth = $currentDate->copy()->endOfMonth();

        // 今月の勤怠データを取得
        $attendances = Attendance::where('user_id', $staff->id)
            ->whereBetween('attendance_at', [$startOfMonth, $endOfMonth])
            ->orderBy('attendance_at', 'asc') // 日付順にソート
            ->get();

        // 今月のカレンダーの日付リストを作成
        $daysInMonth = createCalendarDays($startOfMonth, $endOfMonth, $attendances);

        return view('admin.staff.attendance.index', compact('user', 'staff', 'daysInMonth', 'currentDate',  'prevMonth', 'nextMonth'));
    }

    // CSVファイル出力処理
    public function exportStaffAttendance(Request $request)
    {
        $staffId = $request->input('staffId');
        $staff = User::where('id', $staffId)->first();
        $currentDate = Carbon::parse($request->input('currentDate'));
        $year  = $currentDate->year;
        $month = $currentDate->month;
        $filename = $staff->name . "さんの勤怠(" . $year ."/" . $month .").csv";
        $daysInMonthJson = $request->input('daysInMonth');
        $daysInMonth = json_decode($daysInMonthJson, true);

        // StreamedResponse を利用して CSV ファイルを生成しながら出力
        $response = new StreamedResponse(function () use ($daysInMonth) {
            $handle = fopen('php://output', 'w');
            $header = ['日付', '出勤', '退勤', '休憩', '合計'];
            mb_convert_variables('SJIS-win', 'UTF-8', $header);
            fputcsv($handle, $header);

            // $daysInMonth は、キーが日付（例："2025-02-09"）で、値が勤怠情報の配列（または null）とする
            foreach ($daysInMonth as $date => $attendance) {
                $carbonDate = Carbon::parse($date);
                $dayOfWeekJP = ['日', '月', '火', '水', '木', '金', '土'][$carbonDate->dayOfWeek];
                $dateFormatted = $carbonDate->format('m/d') . "({$dayOfWeekJP})";
                $clockIn    = $attendance ? ($attendance['formatted_clock_in'] ?? '') : '';
                $clockOut   = $attendance ? ($attendance['formatted_clock_out'] ?? '') : '';
                $totalBreak = $attendance ? ($attendance['formatted_total_break'] ?? '') : '';
                $totalWork  = $attendance ? ($attendance['formatted_total_work'] ?? '') : '';

                $row = [$dateFormatted, $clockIn, $clockOut, $totalBreak, $totalWork];
                mb_convert_variables('SJIS-win', 'UTF-8', $row);
                fputcsv($handle, $row);
            }
            fclose($handle);
        }, 200, [
            'Content-Type'        => 'text/csv',
            'Content-Disposition' => 'attachment; filename="' . $filename . '"',
        ]);
        return $response;
    }

    // 申請一覧画面
    public function showRequests(Request $request)
    {
        $tab = $request->tab;
        $user = Auth::user();
        if ($tab === 'approved'){
            $attendances = Attendance::where('status_id', [config('constants.STATUS_APPROVED')])->get();
        }else{
            $attendances = Attendance::where('status_id', [config('constants.STATUS_PENDING')])->get();
        }
        $attendances->transform(function ($attendance) {
            $attendance->formatted_attendance_at = $attendance->attendance_at ? Carbon::parse($attendance->attendance_at)->format('Y/m/d') : null;
            $attendance->formatted_requested_at = $attendance->requested_at ? Carbon::parse($attendance->requested_at)->format('Y/m/d') : null;
            return $attendance;
        });
        return view('admin.applications.index', compact('user', 'attendances', 'tab'));
    }

    // 修正申請承認画面
    public function showApproval(Request $request)
    {
        $user = Auth::user();
        $attendance = Attendance::with('user')
            ->where('id',$request->attendance_id)
            ->whereIn('status_id', [
                config('constants.STATUS_PENDING'),
                config('constants.STATUS_APPROVED')
            ])->first();
        if(!$attendance){
            return redirect()->back()->withErrors(['attendance' => '勤怠データが見つかりませんでした。']);
        }
        $attendance->formatted_year = Carbon::parse($attendance->attendance_at)->format('Y年');
        $attendance->formatted_date = Carbon::parse($attendance->attendance_at)->format('n月j日');
        $attendance->formatted_clock_in =$attendance->clock_in ? Carbon::parse($attendance->clock_in)->format('H:i') : null;
        $attendance->formatted_clock_out = $attendance->clock_out ? Carbon::parse($attendance->clock_out)->format('H:i') : null;
        $breakTimes = BreakTime::where('attendance_id',$request->attendance_id)->get();
        // 休憩データをフォーマット
        $breakTimes->transform(function ($break) {
            $break->break_in = $break->break_in ? Carbon::parse($break->break_in)->format('H:i') : null;
            $break->break_out = $break->break_out ? Carbon::parse($break->break_out)->format('H:i') : null;
            return $break;
        });
        $isApproved = $attendance->status_id === config('constants.STATUS_APPROVED');
        return view('admin.applications.approve', compact('user', 'attendance', 'breakTimes','isApproved'));
    }

    // 承認処理
    public function approve(Request $request)
    {
        $attendance = Attendance::where('id',$request->attendance_id)->where('status_id', config('constants.STATUS_PENDING'))->first();
        if (!$attendance) {
            return redirect()->back()->withErrors(['attendance' => '勤怠データが見つからないため処理を中止しました。']);
        }
        // 他のユーザーに更新されていないことの確認
        $clientUpdatedAt = Carbon::parse($request->updated_at);
        if ($attendance->updated_at->gt($clientUpdatedAt)) {
            return redirect()->back()->withErrors(['attendance' => '他のユーザーが修正処理を行ったので処理を中止しました。']);
        }
        $attendance->status_id = config('constants.STATUS_APPROVED');
        $attendance->save();
        return redirect()->back()->with('message', '勤怠を承認しました');
    }
}
