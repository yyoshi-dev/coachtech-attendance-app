<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AttendanceCorrectionRequest;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class CorrectionRequestController extends Controller
{
    // 申請一覧画面の表示
    public function indexCorrections(Request $request)
    {
        // タブ情報の取得
        $tab = $request->query('tab', 'pending');

        // 不正値はpendingに変換
        if (!in_array($tab, ['pending', 'approved'], true)) {
            $tab = 'pending';
        }

        // 修正テーブルの取得
        $corrections = AttendanceCorrectionRequest::with('attendance.user')
            ->where('status', $tab)
            ->orderByDesc('created_at')
            ->get();

        return view('admin.request.list', compact(
            'tab',
            'corrections',
        ));
    }

    // 修正申請承認画面の表示
    public function detailCorrection($attendance_correct_request_id)
    {
        // 勤怠情報の取得
        $correction = AttendanceCorrectionRequest::with([
            'attendance.user',
        ])
        ->findOrFail($attendance_correct_request_id);


        return view('admin.request.approve', compact('correction'));
    }

    // 修正申請の承認
    public function approveCorrection($attendance_correct_request_id)
    {
        /** @var User $admin */
        $admin = Auth::user();

        // 修正情報の取得
        $correction = AttendanceCorrectionRequest::with([
            'attendanceCorrectionRequestBreaks',
            'attendance.attendanceBreaks',
        ])
        ->findOrFail($attendance_correct_request_id);

        // 修正テーブルのステータス確認
        if ($correction->status !== 'pending') {
            return redirect()
                ->route('admin.attendance.correction.detail',
                ['attendance_correct_request_id' => $attendance_correct_request_id]
                );
        }

        DB::transaction(function () use ($correction, $admin) {
            // 修正レコードの更新
            $correction->update([
                'status' => 'approved',
                'reviewed_at' => now(),
                'reviewer_id' => $admin->id,
            ]);

            // 勤怠の更新
            $correction->attendance->update([
                'clock_in' => $correction->requested_clock_in,
                'clock_out' => $correction->requested_clock_out,
            ]);

            // 休憩レコードの更新
            foreach ($correction->attendanceCorrectionRequestBreaks as $correctionBreak) {
                if (is_null($correctionBreak->attendance_break_id)) {
                    $correction->attendance->attendanceBreaks()->create([
                        'break_start' => $correctionBreak->requested_break_start,
                        'break_end' => $correctionBreak->requested_break_end,
                        'sort_order' => $correctionBreak->sort_order,
                    ]);
                } else {
                    $break = $correction->attendance->attendanceBreaks
                        ->firstWhere('id', $correctionBreak->attendance_break_id);
                    $break->update([
                        'break_start' => $correctionBreak->requested_break_start,
                        'break_end' => $correctionBreak->requested_break_end,
                    ]);
                }
            }
        });

        return redirect()
            ->route('admin.attendance.staff.monthly',[
                'id' => $correction->attendance->user_id,
                'month' => $correction->attendance->work_date->format('Y-m'),
            ]);
    }
}
