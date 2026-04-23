<?php

namespace App\Responses\Fortify;

use Laravel\Fortify\Contracts\LoginResponse as FortifyLoginResponse;

class LoginResponse implements FortifyLoginResponse
{
    // 初回ログイン時の遷移先を指定
    public function toResponse($request)
    {
        $user = $request->user();

        if ($user->role === 'admin') {
            return redirect()->route('admin.attendance.list');
        }

        if (! $user->hasVerifiedEmail()) {
            return redirect()->route('verification.notice');
        }

        return redirect()->route('attendance.index');
    }

}