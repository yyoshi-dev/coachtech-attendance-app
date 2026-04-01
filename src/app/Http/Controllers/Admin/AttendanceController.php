<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreAttendanceCorrectionRequest;
use App\Models\Attendance;
use App\Models\AttendanceCorrectionRequest;
use App\Models\AttendanceCorrectionRequestBreak;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class AttendanceController extends Controller
{
    // 勤怠一覧 (日毎)表示
    public function dailyList(Request $request)
    {
        $requestDate = $request->query('date');
        $targetDate = $requestDate
            ? Carbon::createFromFormat('Y-m-d', $requestDate)
            : today();

        // 勤怠情報を含めたユーザー情報の取得
        $users = User::where('role', 'user')
            ->with(['attendances' => function ($query) use ($targetDate) {
                $query->whereDate('work_date', $targetDate->toDateString())
                    ->with('attendanceBreaks');
            }])
            ->orderBy('id')
            ->get();

        // 日付切り替え用
        $previousDate = $targetDate->copy()->subDay();
        $nextDate = $targetDate->copy()->addDay();

        return view('admin.attendance.list', compact(
            'users',
            'targetDate',
            'previousDate',
            'nextDate',
        ));
    }

    // 勤怠詳細表示
    public function detail($id)
    {
        // 勤怠情報の取得
        $attendance = Attendance::with([
            'user',
            'attendanceBreaks',
            'attendanceCorrectionRequests.attendanceCorrectionRequestBreaks',
        ])->findOrFail($id);

        // 修正レコードのステータス確認
        $latestCorrection = $attendance->latestCorrection;
        $isPending = $latestCorrection && $latestCorrection->status === 'pending';

        return view('admin.attendance.detail', compact(
            'attendance',
            'latestCorrection',
            'isPending',
        ));
    }

    // 直接修正
    public function update(StoreAttendanceCorrectionRequest $request, $id)
    {
        /** @var User $admin */
        $admin = Auth::user();

        // 勤怠情報の取得
        $attendance = Attendance::with([
            'attendanceBreaks',
            'attendanceCorrectionRequests'
        ])->findOrFail($id);

        // 修正テーブルのステータス確認
        if ($attendance->latestCorrection && $attendance->latestCorrection->status === 'pending') {
            return redirect()
                ->route('admin.attendance.detail', ['id' => $attendance->id]);
        }

        $date = $attendance->work_date->toDateString();

        DB::transaction(function () use ($request, $attendance, $admin, $date) {
            // 勤怠修正レコードの作成
            $correction = AttendanceCorrectionRequest::create([
                'attendance_id' => $attendance->id,
                'request_user_id' => $admin->id,
                'requested_clock_in' => "$date {$request->requested_clock_in}",
                'requested_clock_out' => "$date {$request->requested_clock_out}",
                'request_remarks' => $request->request_remarks,
                'status' => 'approved',
                'reviewed_at' => now(),
                'reviewer_id' => $admin->id,
            ]);

            // 勤怠テーブルの更新
            $attendance->update([
                'clock_in' => "$date {$request->requested_clock_in}",
                'clock_out' => "$date {$request->requested_clock_out}",
            ]);

            // 休憩修正レコードの作成と休憩テーブルの更新
            foreach($request->requested_break_start as $index => $start) {
                $end = $request->requested_break_end[$index] ?? null;
                $breakId = $request->attendance_break_id[$index] ?? null;
                $break = $attendance->attendanceBreaks->firstWhere('id', $breakId);

                if (filled($start) && filled($end)) {
                    // 休憩修正レコードの作成
                    AttendanceCorrectionRequestBreak::create([
                        'attendance_correction_request_id' => $correction->id,
                        'attendance_break_id' => $breakId,
                        'requested_break_start' => "$date {$start}",
                        'requested_break_end' => "$date {$end}",
                        'sort_order' => $index + 1,
                    ]);

                    // 休憩テーブルの更新
                    if ($break) {
                        $break->update([
                            'break_start' => "$date {$start}",
                            'break_end' => "$date {$end}",
                        ]);
                    } else {
                        $attendance->attendanceBreaks()->create([
                            'break_start' => "$date {$start}",
                            'break_end' => "$date {$end}",
                            'sort_order' => $index + 1,
                        ]);
                    }
                }
            }
        });

        return redirect()->route('admin.attendance.list'); // リダイレクト先は確認中
    }


    // スタッフ別勤怠一覧表示
    public function staffMonthlyList($id)
    {
        return view('admin.attendance.staff-monthly');
    }
}
