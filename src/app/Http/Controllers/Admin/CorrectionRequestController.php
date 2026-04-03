<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AttendanceCorrectionRequest;
use Illuminate\Http\Request;

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
        return view('admin.request.approve');
    }
}
