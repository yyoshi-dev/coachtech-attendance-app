<?php

namespace App\Http\Controllers;

use App\Models\AttendanceCorrectionRequest;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class CorrectionRequestEntryController extends Controller
{
    // 申請一覧画面の表示
    public function index(Request $request)
    {
        // ユーザーロール
        $role = $request->attributes->get('user_role');

        // タブ情報の取得
        $tab = $request->query('tab', 'pending');

        // 不正値はpendingに変換
        if (! in_array($tab, ['pending', 'approved'], true)) {
            $tab = 'pending';
        }

        // 一般ユーザーの場合
        if ($role === 'user') {
            /** @var \App\Models\User $user */
            $user = Auth::user();

            $corrections = AttendanceCorrectionRequest::with('attendance.user')
                ->where('request_user_id', $user->id)
                ->where('status', $tab)
                ->orderByDesc('created_at')
                ->get();

            return view('user.request.list', compact(
                'tab',
                'user',
                'corrections',
            ));

        // 管理者の場合
        } else {
            $corrections = AttendanceCorrectionRequest::with('attendance.user')
                ->where('status', $tab)
                ->orderByDesc('created_at')
                ->get();

            return view('admin.request.list', compact(
                'tab',
                'corrections',
            ));
        }
    }
}
