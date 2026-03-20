<?php

namespace App\Http\Controllers\User;

use App\Http\Controllers\Controller;
use App\Models\Attendance;
use App\Models\AttendanceBreak;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class AttendanceController extends Controller
{
    // 勤怠登録画面（一般ユーザー）の表示
    public function showAttendancePage()
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

        return view('user.attendance.index', compact('status', 'currentDateTime'));
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
}
