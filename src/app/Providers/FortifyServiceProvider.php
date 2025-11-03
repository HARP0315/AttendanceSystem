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
            return view('auth.staff-register');
        });

        /**
         * ログイン画面
         * roleで画面切り替え
         */
        Fortify::loginView(function (Request $request) {
            if ($request->is('admin/*')) {
                return view('admin.auth.login');
            }

            return view('auth.staff-login');
        });

        /**
         * 新規登録処理
         * スタッフ (role: 1) の登録のみを処理
         */
        Fortify::createUsersUsing(CreateNewUser::class);

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


        app()->bind(AuthenticatedSessionController::class, function () {
            return new class extends AuthenticatedSessionController {

            /**
             * ログイン後の遷移制御
             * roleに応じて遷移先を変更
             * スタッフのみメール認証をチェック
             */
            protected function authenticated(LoginRequest $request)
            {
                $user = Auth::user();
                if ($user->role === 0) {
                    return redirect('/admin/attendance/list');
                }
                if ($user->role === 1) {
                    if ($user instanceof MustVerifyEmail && ! $user->hasVerifiedEmail()) {
                        return app(config('fortify.responses.email_verification_prompt'));
                    }
                    return redirect('/attendance');
                }
            }

            /**
             * ログアウト後の遷移制御
             * ログアウト処理後は Auth::user() が使えないため、
             * リクエスト元のURLを見て遷移先を判断
             */
            public function destroy(Request $request)
            {
                parent::destroy($request);

                $referer = $request->headers->get('referer');
                if ($referer && str_contains($referer, url('/admin'))) {
                    return redirect('/admin/login');
                }
                return redirect('/login');
            }
        };
    });

        $this->app->singleton(AuthenticatedSessionController::class, function ($app) {

            return new class(
                $app[StatefulGuard::class]
            ) extends AuthenticatedSessionController {

                /**
                 * ログイン後の遷移制御
                 */
                public function store(FortifyLoginRequest $request)
                {
                    // 本来のログイン処理を実行
                    return $this->loginPipeline($request)->then(function ($request) {

                        $user = Auth::user();

                        // 0: 管理者
                        if ($user->role === 0) {
                            return redirect('/admin/attendance/list');
                        }

                        // 1: スタッフ
                        if ($user->role === 1) {
                            // スタッフ、かつ、メール認証がまだ
                            if ($user instanceof MustVerifyEmail && ! $user->hasVerifiedEmail()) {
                                return app(config('fortify.responses.email_verification_prompt'));
                            }
                            // 認証済みスタッフ
                            return redirect('/attendance');
                        }

                    });
                }


                /**
                 * ログアウト後の遷移制御
                 */
                public function destroy(Request $request)
                {

                    $this->guard->logout();
                    $request->session()->invalidate();
                    $request->session()->regenerateToken();

                    $referer = $request->headers->get('referer');

                    if ($referer && str_contains($referer, url('/admin'))) {
                        return redirect('/admin/login');
                    }

                    return redirect('/login');
                }
            };
        });

        app()->bind(FortifyLoginRequest::class, LoginRequest::class);
    }
}
