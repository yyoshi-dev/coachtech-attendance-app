<?php

namespace App\Http\Controllers\User;

use App\Http\Controllers\Controller;
use App\Models\Attendance;
use App\Models\AttendanceBreak;
use Carbon\Carbon;
use Carbon\CarbonPeriod;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class AttendanceController extends Controller
{
    // 勤怠登録画面（一般ユーザー）の表示
    public function index()
    {
        // 日時情報の取得
        $now = now();
        $workDate = $now->toDateString();
        $currentDateTime = $now->toIso8601String();

        // ステータスの取得
        $attendance = Attendance::with('attendanceBreaks')
            ->where('user_id', Auth::id())
            ->where('work_date', $workDate)
            ->first();

        $status = $attendance ? $attendance->status : '勤務外';
        $isAfterWork = $attendance && $attendance->status === '退勤済';

        return view('user.attendance.index', compact(
            'status',
            'currentDateTime',
            'isAfterWork'
        ));
    }

    // 出勤登録
    public function clockIn()
    {
        // 日時情報の取得
        $now = now();
        $workDate = $now->toDateString();

        // 既存データの確認
        $exists = Attendance::query()
            ->where('user_id', Auth::id())
            ->where('work_date', $workDate)
            ->exists();

        // 出勤登録
        if (!$exists) {
            Attendance::create([
                'user_id' => Auth::id(),
                'work_date' => $workDate,
                'clock_in' => $now,
            ]);
        }

        return redirect()->route('attendance.index');
    }

    // 退勤登録
    public function clockOut()
    {
        // 日時情報の取得
        $now = now();
        $workDate = $now->toDateString();

        // 勤怠の取得
        $attendance = Attendance::query()
            ->where('user_id', Auth::id())
            ->where('work_date', $workDate)
            ->first();

        // 出勤していない場合、何もしない
        if (!$attendance) {
            return redirect()->route('attendance.index');
        }

        // 出勤中の場合、退勤登録
        if ($attendance->status === '出勤中') {
            $attendance->update([
                'clock_out' => $now,
            ]);
        }

        return redirect()->route('attendance.index');
    }

    // 休憩入登録
    public function breakStart()
    {
        // 日時情報の取得
        $now = now();
        $workDate = $now->toDateString();

        // 勤怠の取得
        $attendance = Attendance::with('attendanceBreaks')
            ->where('user_id', Auth::id())
            ->where('work_date', $workDate)
            ->first();

        // 出勤していない場合、何もしない
        if (!$attendance) {
            return redirect()->route('attendance.index');
        }

        // 出勤中の場合、休憩入を登録
        if ($attendance->status === '出勤中') {
            $nextSortOrder = (AttendanceBreak::where('attendance_id', $attendance->id)
                ->max('sort_order') ?? 0) + 1;

            AttendanceBreak::create([
                'attendance_id' => $attendance->id,
                'break_start' => $now,
                'sort_order' => $nextSortOrder,
            ]);
        }

        return redirect()->route('attendance.index');
    }

    // 休憩戻登録
    public function breakEnd()
    {
        // 日時情報の取得
        $now = now();
        $workDate = $now->toDateString();

        // 勤怠の取得
        $attendance = Attendance::with('attendanceBreaks')
            ->where('user_id', Auth::id())
            ->where('work_date', $workDate)
            ->first();

        // 出勤していない場合、何もしない
        if (!$attendance) {
            return redirect()->route('attendance.index');
        }

        // 休憩中の場合、休憩戻を登録
        if ($attendance->status === '休憩中') {
            $latestBreak = $attendance->latestBreak;

            if ($latestBreak) {
                $latestBreak->update([
                    'break_end' => $now,
                ]);
            }
        }

        return redirect()->route('attendance.index');
    }

    // 勤怠一覧表示
    public function list(Request $request)
    {
        // 表示対象月の決定
        $month = $request->query('month');
        $targetDate = $month
            ? Carbon::createFromFormat('Y-m', $month)->startOfMonth()
            : now()->startOfMonth();

        $start = $targetDate;
        $end = $targetDate->copy()->endOfMonth();
        $period = CarbonPeriod::create($start, $end);

        // 対象月の勤怠一覧取得
        $attendances = Attendance::with('attendanceBreaks')
            ->where('user_id', Auth::id())
            ->whereBetween('work_date', [$start, $end])
            ->get()
            ->keyBy(fn ($attendance) => $attendance->work_date->toDateString());

        // 月切り替え用
        $currentMonth = $targetDate->format('Y/m');
        $previousMonth = $targetDate->copy()->subMonth()->format('Y-m');
        $nextMonth = $targetDate->copy()->addMonth()->format('Y-m');

        return view('user.attendance.list', compact(
            'period',
            'attendances',
            'currentMonth',
            'previousMonth',
            'nextMonth'
        ));
    }

    // 勤怠詳細表示 (既存レコード)
    public function detail($id)
    {
        /** @var User $user */
        $user = Auth::user();

        // 勤怠情報の取得
        $attendance = Attendance::with([
                'attendanceBreaks',
                'attendanceCorrectionRequests',
            ])
            ->where('id', $id)
            ->where('user_id', $user->id)
            ->firstOrFail();

        return view('user.attendance.detail', compact('user', 'attendance'));
    }
}
