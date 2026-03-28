<?php

namespace App\Http\Controllers\User;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreAttendanceCorrectionRequest;
use App\Models\Attendance;
use App\Models\AttendanceCorrectionRequest;
use App\Models\AttendanceCorrectionRequestBreak;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class CorrectionRequestController extends Controller
{
    // 修正申請
    public function storeCorrection(StoreAttendanceCorrectionRequest $request, $id)
    {
        /** @var User $user */
        $user = Auth::user();

        $attendance = Attendance::query()
            ->where('id', $id)
            ->where('user_id', $user->id)
            ->firstOrFail();

        $date = $attendance->work_date->toDateString();

        // 勤怠修正テーブルの作成
        $correction = AttendanceCorrectionRequest::create([
            'attendance_id' => $attendance->id,
            'request_user_id' => $user->id,
            'requested_clock_in' => "$date {$request->requested_clock_in}",
            'requested_clock_out' => "$date {$request->requested_clock_out}",
            'request_remarks' => $request->request_remarks,
            'status' => 'pending',
        ]);

        // 休憩修正テーブルの作成
        foreach ($request->requested_break_start as $index => $start) {
            $end = $request->requested_break_end[$index] ?? null;
            $breakId = $request->attendance_break_id[$index] ?? null;

            if (filled($start) && filled($end)) {
                AttendanceCorrectionRequestBreak::create([
                    'attendance_correction_request_id' => $correction->id,
                    'attendance_break_id' => $breakId,
                    'requested_break_start' => "$date {$start}",
                    'requested_break_end' => "$date {$end}",
                    'sort_order' => $index + 1,
                ]);
            }
        }

        return redirect()->route('attendance.corrections.index');
    }

    // 申請一覧
    public function indexCorrections(Request $request)
    {
        // タブ情報の取得
        $tab = $request->query('tab', 'pending');

        // 不正値はpendingに変換
        if (!in_array($tab, ['pending', 'approved'], true)) {
            $tab = 'pending';
        }

        /** @var User $user */
        $user = Auth::user();

        // 修正データの取得
        $corrections = AttendanceCorrectionRequest::with([
            'attendanceCorrectionRequestBreaks',
            'attendance.attendanceBreaks',
            'requestUser'
            ])
            ->where('request_user_id', $user->id)
            ->where('status', $tab)
            ->orderByDesc('created_at')
            ->get();

        return view('user.request.list', compact(
            'tab',
            'user',
            'corrections',
        ));
    }
}
