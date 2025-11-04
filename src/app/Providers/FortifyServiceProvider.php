<?php

namespace App\Providers;

use App\Actions\Fortify\CreateNewUser;
use App\Actions\Fortify\ResetUserPassword;
use App\Actions\Fortify\UpdateUserPassword;
use App\Actions\Fortify\UpdateUserProfileInformation;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Str;
use Laravel\Fortify\Actions\RedirectIfTwoFactorAuthenticatable;
use Laravel\Fortify\Fortify;
use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Auth;
use Illuminate\Contracts\Auth\MustVerifyEmail;
use App\Http\Requests\LoginRequest;
use Laravel\Fortify\Http\Requests\LoginRequest as FortifyLoginRequest;
use Laravel\Fortify\Http\Controllers\AuthenticatedSessionController;
use App\Actions\Fortify\LoginResponse;
use App\Actions\Fortify\LogoutResponse;

class FortifyServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        Fortify::createUsersUsing(CreateNewUser::class);
        Fortify::updateUserProfileInformationUsing(UpdateUserProfileInformation::class);
        Fortify::updateUserPasswordsUsing(UpdateUserPassword::class);
        Fortify::resetUserPasswordsUsing(ResetUserPassword::class);
        Fortify::redirectUserForTwoFactorAuthenticationUsing(RedirectIfTwoFactorAuthenticatable::class);

        RateLimiter::for('login', function (Request $request) {
            $throttleKey = Str::transliterate(Str::lower($request->input(Fortify::username())).'|'.$request->ip());

            return Limit::perMinute(5)->by($throttleKey);
        });

        RateLimiter::for('two-factor', function (Request $request) {
            return Limit::perMinute(5)->by($request->session()->get('login.id'));
        });

        /**
         * 登録画面 (スタッフ用)
         * 管理者登録はここでは想定しない
         */
        Fortify::registerView(function () {
            return view('auth.register');
        });

        /**
         * ログイン画面
         * roleで画面切り替え
         */
        Fortify::loginView(function (Request $request) {
            if ($request->is('admin/*')) {
                return view('admin.auth.login');
            }

            return view('auth.login');
        });

        /**
         * 新規登録処理
         * スタッフ (role: 1) の登録のみを処理
         */
        Fortify::createUsersUsing(CreateNewUser::class);

        // メール認証用ビュー
        Fortify::verifyEmailView(function () {
            return view('auth.verify-email');
        });

        /**
         * ログイン認証処理
         * emailとpasswordが一致するかを検証
         * 画面ごとにログインできるroleも制限
         */
        Fortify::authenticateUsing(function (Request $request) {
            $user = User::where('email', $request->email)->first();

            if ($user && Hash::check($request->password, $user->password)) {

                // アドミン画面 からは管理者だけログインできる
                if ($request->is('admin/*') && $user->role !== 0) {
                    return null;
                }
                // スタッフ画面 からはスタッフだけログインできる
                if (! $request->is('admin/*') && $user->role !== 1) {
                    return null;
                }

                // 認証成功
                return $user;
            }

            return null;
        });

        // ----------------------------------------
        // リダイレクト（遷移）制御
        // ----------------------------------------


        // ログイン後レスポンス
        $this->app->singleton(
            \Laravel\Fortify\Contracts\LoginResponse::class,
            LoginResponse::class
        );

        // ログアウト後レスポンス
        $this->app->singleton(
            \Laravel\Fortify\Contracts\LogoutResponse::class,
            LogoutResponse::class
        );

        app()->bind(FortifyLoginRequest::class, LoginRequest::class);
    }
}
