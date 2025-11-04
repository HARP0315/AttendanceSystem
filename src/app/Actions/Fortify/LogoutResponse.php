<?php

namespace App\Actions\Fortify;

use Laravel\Fortify\Contracts\LogoutResponse as LogoutResponseContract;
use Illuminate\Http\Request;

class LogoutResponse implements LogoutResponseContract
{
    /**
     * Handle the logout response.
     */
    public function toResponse($request)
    {
        $referer = $request->headers->get('referer');

        if ($referer && str_contains($referer, url('/admin'))) {
            return redirect('/admin/login');
        }

        return redirect('/login');
    }
}
