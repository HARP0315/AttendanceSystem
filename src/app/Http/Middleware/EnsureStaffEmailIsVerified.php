<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureStaffEmailIsVerified
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        // スタッフだけメール認証を要求
        if ($user && $user->role === 1 && !$user->hasVerifiedEmail()) {
            return redirect()->route('verification.notice');
        }

        return $next($request);

    }
}
