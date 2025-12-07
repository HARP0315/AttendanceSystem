<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\View;
use Illuminate\Support\Facades\Auth;
use App\Models\Attendance;
use Carbon\Carbon;

class ViewServiceProvider extends ServiceProvider
{
    /**
     * Register services.
     */
    public function register(): void
    {
        //
    }

    /**
     * ヘッダーでの表示内容を制御するためにView Composerを登録
     */
    public function boot(): void
    {
        View::composer('*', function ($view) {
            $user = Auth::user();
            $headerLink = null;

            if ($user && $user->role === 1) {
                $today = Carbon::today()->toDateString();
                $headerLink = Attendance::where('user_id', $user->id)
                                        ->where('work_date', $today)
                                        ->where('status','=',3)
                                        ->where('is_deleted', '!=', 1)
                                        ->first();
            }

            $view->with('headerLink', $headerLink);
        });
    }
}
