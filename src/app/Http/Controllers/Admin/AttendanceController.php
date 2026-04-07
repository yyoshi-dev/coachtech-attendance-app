<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreAttendanceCorrectionRequest;
use App\Models\Attendance;
use App\Models\AttendanceCorrectionRequest;
use App\Models\AttendanceCorrectionRequestBreak;
use App\Models\User;
use Carbon\Carbon;
use Carbon\CarbonPeriod;
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

        return redirect()
            ->route('admin.attendance.staff.monthly',[
                'id' => $attendance->user_id,
                'month' => $attendance->work_date->format('Y-m'),
            ]);
    }

    // スタッフ別勤怠一覧表示
    public function staffMonthlyList(Request $request, $id)
    {
        $staff = User::where('role', 'user')->findOrFail($id);

        // 表示対象月
        $requestMonth = $request->query('month');
        $targetDate = $requestMonth
            ? Carbon::createFromFormat('Y-m', $requestMonth)->startOfMonth()
            : now()->startOfMonth();

        $start = $targetDate->copy();
        $end = $targetDate->copy()->endOfMonth();
        $period = CarbonPeriod::create($start, $end);

        // 勤怠一覧の取得
        $attendances = Attendance::with('attendanceBreaks')
            ->where('user_id', $id)
            ->whereBetween('work_date', [$start, $end])
            ->get()
            ->keyBy(fn ($attendance) => $attendance->work_date->toDateString());

        // 月切り替え用
        $currentMonth = $targetDate->format('Y/m');
        $previousMonth = $targetDate->copy()->subMonth()->format('Y-m');
        $nextMonth = $targetDate->copy()->addMonth()->format('Y-m');

        $currentMonthForExport = $targetDate->format('Y-m');

        return view('admin.attendance.staff-monthly', compact(
            'staff',
            'period',
            'attendances',
            'currentMonth',
            'previousMonth',
            'nextMonth',
            'currentMonthForExport',
        ));
    }

    // CSV出力
    public function export(Request $request, $id)
    {
        $staff = User::where('role', 'user')->findOrFail($id);

        // 出力対象期間の作成
        $requestMonth = $request->query('month');
        $targetDate = $requestMonth
            ? Carbon::createFromFormat('Y-m', $requestMonth)->startOfMonth()
            : now()->startOfMonth();

        $start = $targetDate->copy();
        $end = $targetDate->copy()->endOfMonth();
        $period = CarbonPeriod::create($start, $end);

        // 勤怠一覧の取得
        $attendances = Attendance::with('attendanceBreaks')
            ->where('user_id', $staff->id)
            ->whereBetween('work_date', [$start, $end])
            ->get()
            ->keyBy(fn ($attendance) => $attendance->work_date->toDateString());

        // 出力データの作成
        $csvData = [];

        foreach ($period as $date) {
            $dateStr = $date->format('Y-m-d');
            $attendance = $attendances->get($dateStr);

            if ($attendance) {
                $csvData[] = [
                    'work_date' => $date->isoFormat('YYYY/MM/DD(ddd)'),
                    'clock_in' => $attendance->clock_in?->format('H:i') ?? '',
                    'clock_out' => $attendance->clock_out?->format('H:i') ?? '',
                    'breakTotal' => $attendance->breakTotalFormatted,
                    'workTotal' => $attendance->workTotalFormatted,
                ];
            } else {
                $csvData[] = [
                    'work_date' => $date->isoFormat('YYYY/MM/DD(ddd)'),
                    'clock_in' => '',
                    'clock_out' => '',
                    'breakTotal' => '',
                    'workTotal' => '',
                ];
            }
        }

        // csvを文字列として組み立てる
        $csv = "日付,出勤,退勤,休憩,合計\n";
        foreach ($csvData as $row) {
            $csv .= implode(',', $row) . "\n";
        }

        $csv = "\xEF\xBB\xBF" . $csv; // BOM付UTF-8

        $filename = sprintf(
            'attendance_%s_%s_%s.csv',
            $staff->id,
            preg_replace('/[ 　]+/u', '_', $staff->name),
            $targetDate->format('Ym')
        );

        return response($csv)
            ->header('Content-type', 'text/csv; charset=UTF-8')
            ->header(
                'Content-Disposition',
                'attachment; filename="' . $filename . '"'
            );
    }
}
