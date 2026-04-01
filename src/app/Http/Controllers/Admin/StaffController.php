<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;

class StaffController extends Controller
{
    // スタッフ一覧の表示
    public function list()
    {
        // スタッフ一覧の取得
        $staffs = User::where('role', 'user')
            ->orderBy('id')
            ->get(['id', 'name', 'email']);

        return view('admin.staff.list', compact('staffs'));
    }
}
