<?php

namespace App\Responses\Fortify;

use Laravel\Fortify\Contracts\VerifyEmailResponse as FortifyVerifyEmailResponse;
use Illuminate\Http\RedirectResponse;

class VerifyEmailResponse implements FortifyVerifyEmailResponse
{
    public function toResponse($request): RedirectResponse
    {
        // メール認証完了後のリダイレクト先を指定
        return redirect()->route('attendance.index');
    }
}
