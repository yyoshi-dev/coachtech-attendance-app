<?php

namespace App\Responses\Fortify;

use Laravel\Fortify\Contracts\RegisterResponse as FortifyRegisterResponse;

class RegisterResponse implements FortifyRegisterResponse
{
    public function toResponse($request)
    {
        return redirect()->route('verification.notice');
    }
}