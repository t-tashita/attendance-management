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
use Laravel\Fortify\Fortify;
use Laravel\Fortify\Contracts\LogoutResponse;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;

class FortifyServiceProvider extends ServiceProvider
{
    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        Fortify::verifyEmailView(function () {
            return view('user.verify-email');
        });

        Fortify::createUsersUsing(CreateNewUser::class);

        Fortify::registerView(function () {
            return view('user.register');
        });

        Fortify::loginView(function () {
            return view('user.login');
        });

        RateLimiter::for('login', function (Request $request) {
            $email = (string) $request->email;
            return Limit::perMinute(10)->by($email . $request->ip());
        });

        // 管理者ログイン画面
        Fortify::authenticateUsing(function (Request $request) {
                // 通常ユーザー認証
                $user = \App\Models\User::where('email', $request->email)->first();
                if ($user && Hash::check($request->password, $user->password)) {
                    return $user;
                }
                throw ValidationException::withMessages([
                    Fortify::username() => ['ログイン情報が登録されていません。'],
                ]);
        });

        app()->bind(FortifyLoginRequest::class, LoginRequest::class);
    }

    public function register()
    {
        $this->app->instance(LogoutResponse::class, new class implements LogoutResponse {
            public function toResponse($request)
            {
                return redirect('/login');
            }
        });
    }
}
