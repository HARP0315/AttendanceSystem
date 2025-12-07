<?php

namespace App\Actions\Fortify;

use Laravel\Fortify\Contracts\LoginResponse as LoginResponseContract;
use Illuminate\Support\Facades\Auth;

class LoginResponse implements LoginResponseContract
{
    /**
     * 管理者とスタッフでログイン後の遷移先をコントロール
     */
    public function toResponse($request)
    {
        $user = Auth::user();

        if ($user->role === 0) {
            return redirect()->route('admin.attendance.list');
        }

        if ($user->role === 1) {
            if (!$user->hasVerifiedEmail()) {
                return redirect()->route('verification.notice');
            }
            return redirect()->route('attendance.view');
        }

        abort(404);
    }
}
