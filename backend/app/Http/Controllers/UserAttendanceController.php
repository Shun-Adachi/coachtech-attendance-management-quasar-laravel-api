<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Http\Requests\SubmitCorrectionRequest;
use App\Models\Attendance;
use App\Models\BreakTime;
use Carbon\Carbon;

class UserAttendanceController extends Controller
{
    // 勤怠登録画面表示
    public function create(Request $request)
    {
        $user = Auth::user();
        $today = Carbon::today();
        $attendance = Attendance::where('user_id', $user->id)->whereDate('attendance_at', $today)->first();
        // 今日の勤怠が存在しない場合、新規作成
        if (!$attendance) {
            $attendance = Attendance::create([
                'user_id' => $user->id,
                'attendance_at' => $today,
                'status_id' => config('constants.STATUS_ATTENDANCE'),
            ]);
        }
        return view('attendance.register', compact('user', 'attendance'));
    }

    // 勤怠登録処理
    public function register(Request $request)
    {
        $user = Auth::user();
        $today = Carbon::today();
        $attendance = Attendance::where('user_id', $user->id)->whereDate('attendance_at', $today)->first();

        // 勤務前の場合、勤務中ステータスに変更
        if ($attendance->status_id === config('constants.STATUS_ATTENDANCE')) {
            Attendance::where('id', $attendance->id)->update([
                'status_id' => config('constants.STATUS_WORKING'),
                'clock_in' => now(),
            ]);
            $attendance = Attendance::where('user_id', $user->id)->whereDate('attendance_at', $today)->first();
            session()->flash('message', '勤務開始時刻を登録しました');
        }
        // 勤務中の場合、退勤ステータスに変更
        elseif ($attendance->status_id === config('constants.STATUS_WORKING')) {
            Attendance::where('id', $attendance->id)->update([
                'status_id' => config('constants.STATUS_LEAVING'),
                'clock_out' => now(),
            ]);
            $attendance = Attendance::where('user_id', $user->id)->whereDate('attendance_at', $today)->first();
            session()->flash('message', '勤務を終了しました');
        }
        return redirect('/attendance');
    }

    // 休憩処理
    public function break(Request $request)
    {
        $user = Auth::user();
        $today = Carbon::today();
        $attendance = Attendance::where('user_id', $user->id)->whereDate('attendance_at', $today)->first();

        // 勤務中の場合、休憩入り処理を行う
        if ($attendance->status_id === config('constants.STATUS_WORKING')) {
            Attendance::where('id', $attendance->id)->update([
                'status_id' => config('constants.STATUS_BREAK'),
            ]);
            BreakTime::create([
                'attendance_id' => $attendance->id,
                'break_in' => now(),
            ]);
            session()->flash('message', '休憩を開始しました');
        }
        // 休憩中の場合、休憩戻り処理を行う
        else if($attendance->status_id === config('constants.STATUS_BREAK')){
            Attendance::where('id', $attendance->id)->update([
                'status_id' => config('constants.STATUS_WORKING'),
            ]);
            BreakTime::where('attendance_id', $attendance->id)->whereNull('break_out')->first()->update([
                'attendance_id' => $attendance->id,
                'break_out' => now(),
            ]);
            session()->flash('message', '休憩を終了しました');}

        return redirect('/attendance');
    }

    // 勤怠一覧画面表示
    public function index(Request $request, $year = null, $month = null)
    {
        $user = Auth::user();
        Carbon::setLocale('ja');
        $currentDate = $year && $month ? Carbon::create($year, $month, 1) : Carbon::now();
        $prevMonth = $currentDate->copy()->subMonth();
        $nextMonth = $currentDate->copy()->addMonth();
        $startOfMonth = $currentDate->copy()->startOfMonth();
        $endOfMonth = $currentDate->copy()->endOfMonth();

        // 今月の勤怠データを取得
        $attendances = Attendance::where('user_id', $user->id)
            ->whereBetween('attendance_at', [$startOfMonth, $endOfMonth])
            ->orderBy('attendance_at', 'asc') // 日付順にソート
            ->get();

        // 今月のカレンダーの日付リストを作成
        $daysInMonth = createCalendarDays($startOfMonth, $endOfMonth, $attendances);

        return view('attendance.index', compact('user', 'daysInMonth', 'currentDate', 'prevMonth', 'nextMonth'));
    }

    // 勤怠詳細画面表示
    public function showAttendance(Request $request)
    {
        $user = Auth::user();
        $attendance = Attendance::with('user')->where('user_id',$user->id)->where('id',$request->attendance_id)->first();
        $attendance->formatted_year = Carbon::parse($attendance->attendance_at)->format('Y年');
        $attendance->formatted_date = Carbon::parse($attendance->attendance_at)->format('n月j日');
        $attendance->formatted_clock_in =$attendance->clock_in ? Carbon::parse($attendance->clock_in)->format('H:i') : null;
        $attendance->formatted_clock_out = $attendance->clock_out ? Carbon::parse($attendance->clock_out)->format('H:i') : null;
        $breakTimes = BreakTime::where('attendance_id',$request->attendance_id)->get();
        $breakTimes->transform(function ($break) {
            $break->break_in = $break->break_in ? Carbon::parse($break->break_in)->format('H:i') : null;
            $break->break_out = $break->break_out ? Carbon::parse($break->break_out)->format('H:i') : null;
            return $break;
        });
        $isSubmitted = $attendance->status_id===config('constants.STATUS_PENDING') || $attendance->status_id===config('constants.STATUS_APPROVED');
        return view('attendance.show', compact('user', 'attendance', 'breakTimes','isSubmitted'));
    }

    // 修正申請処理
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
        $attendance->status_id = config('constants.STATUS_PENDING');
        $attendance->requested_at = now();
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
        return redirect()->back()->with('message', '修正申請を提出しました');
    }

    // 申請一覧画面表示
    public function showRequests(Request $request)
    {
        $tab = $request->tab;
        $user = Auth::user();
        if ($tab === 'approved'){
            $attendances = Attendance::where('user_id',$user->id)
                ->where('status_id', [config('constants.STATUS_APPROVED')])->get();
        }else{
            $attendances = Attendance::where('user_id',$user->id)
                ->where('status_id', [config('constants.STATUS_PENDING')])->get();
        }
        $attendances->transform(function ($attendance) {
            $attendance->formatted_attendance_at = $attendance->attendance_at ? Carbon::parse($attendance->attendance_at)->format('Y/m/d') : null;
            $attendance->formatted_requested_at = $attendance->requested_at ? Carbon::parse($attendance->requested_at)->format('Y/m/d') : null;
            return $attendance;
        });
        return view('applications.index', compact('user', 'attendances', 'tab'));
    }
}
