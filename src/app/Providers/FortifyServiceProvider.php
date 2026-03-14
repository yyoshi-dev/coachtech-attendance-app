<?php

namespace App\Providers;

use App\Actions\Fortify\CreateNewUser;
use App\Models\User;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Laravel\Fortify\Fortify;

use Laravel\Fortify\Http\Requests\LoginRequest as FortifyLoginRequest;
use App\Http\Requests\LoginRequest as CustomLoginRequest;

use Laravel\Fortify\Contracts\RegisterResponse as FortifyRegisterResponse;
use App\Responses\Fortify\RegisterResponse as CustomRegisterResponse;

use Laravel\Fortify\Contracts\VerifyEmailResponse as FortifyVerifyEmailResponse;
use App\Responses\Fortify\VerifyEmailResponse as CustomVerifyEmailResponse;

use Laravel\Fortify\Contracts\LoginResponse as FortifyLoginResponse;
use App\Responses\Fortify\LoginResponse as CustomLoginResponse;

use Laravel\Fortify\Contracts\LogoutResponse as FortifyLogoutResponse;
use App\Responses\Fortify\LogoutResponse as CustomLogoutResponse;

class FortifyServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        // FortifyのLoginRequestを置き換え
        $this->app->bind(FortifyLoginRequest::class, CustomLoginRequest::class);

        // 会員登録後にメール認証誘導画面に遷移するよう置き換え
        $this->app->bind(FortifyRegisterResponse::class, CustomRegisterResponse::class);

        // メール認証後のリダイレクト先を置き換え
        $this->app->bind(FortifyVerifyEmailResponse::class, CustomVerifyEmailResponse::class);

        // 初回ログイン時のリダイレクト先を設定
        $this->app->bind(FortifyLoginResponse::class, CustomLoginResponse::class);

        // ログアウト時のリダイレクト先の設定を置き換え
        $this->app->bind(FortifyLogoutResponse::class, CustomLogoutResponse::class);
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        // ユーザー登録
        Fortify::registerView(function () {
            return view('user.auth.register');
        });

        // ユーザー及び管理者のログイン
        Fortify::loginView(function (Request $request) {
            if ($request->is('admin/login')) {
                return view('admin.auth.login');
            }

            return view('user.auth.login');
        });

        // ユーザー作成
        Fortify::createUsersUsing(CreateNewUser::class);

        // ログイン認証
        Fortify::authenticateUsing(function (Request $request) {
            // ロールを定義
            $role = $request->login_type === 'admin' ? 'admin': 'user';

            // ユーザーを取得
            $user = User::where('email', $request->email)
                ->where('role', $role)
                ->first();

            // パスワード一致を確認
            if ($user && Hash::check($request->password, $user->password)) {
                return $user;
            }

            // 認証失敗の場合
            throw ValidationException::withMessages([
                'password' => ['ログイン情報が登録されていません'],
            ]);
        });

        RateLimiter::for('login', function (Request $request) {
            $throttleKey = Str::transliterate(
                Str::lower($request->input(Fortify::username())).'|'.$request->ip()
            );

            return Limit::perMinute(5)->by($throttleKey);
        });
    }
}
