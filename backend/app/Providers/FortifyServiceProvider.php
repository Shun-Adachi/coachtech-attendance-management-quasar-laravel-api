<?php

namespace App\Providers;

use App\Actions\Fortify\CreateNewUser;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\Auth;
use Laravel\Fortify\Fortify;

class FortifyServiceProvider extends ServiceProvider
{

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        Fortify::createUsersUsing(CreateNewUser::class);
        Fortify::registerView(function () {
            return view('auth.register');
        });

        RateLimiter::for('login', function (Request $request) {
            $email = (string) $request->email;

            return Limit::perMinute(10)->by($email . $request->ip());
        });

        // 会員登録後勤怠ページにリダイレクト
        $this->app->singleton(\Laravel\Fortify\Contracts\RegisterResponse::class, function ($app) {
            return new class implements \Laravel\Fortify\Contracts\RegisterResponse {
                public function toResponse($request)
                {
                    return redirect('/attendance');
                }
            };
        });

        // ログイン後のリダイレクト先を変更
        $this->app->singleton(\Laravel\Fortify\Contracts\LoginResponse::class, function ($app) {
            return new class implements \Laravel\Fortify\Contracts\LoginResponse {
                public function toResponse($request)
                {
                    $user = Auth::user();

                    if ($user->role_id === 1) {
                        // 管理者の場合のリダイレクト先
                        return redirect('/admin/attendance');
                    }

                    // 一般ユーザーの場合のリダイレクト先
                    return redirect('/attendance');
                }
            };
        });
    }
}
