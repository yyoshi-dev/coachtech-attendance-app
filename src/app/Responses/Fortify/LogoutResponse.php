<?php

namespace App\Responses\Fortify;

use Laravel\Fortify\Contracts\LogoutResponse as FortifyLogoutResponse;

class LogoutResponse implements FortifyLogoutResponse
{
    // ログアウト時のリダイレクト先を設定
    public function toResponse($request)
    {
        return $request->routeIs('admin.logout')
            ? redirect('/admin/login')
            : redirect('/login');
    }
}