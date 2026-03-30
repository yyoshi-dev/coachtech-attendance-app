<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Http\Request;

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
    public function detail()
    {
        return view('admin.attendance.detail');
    }
}
