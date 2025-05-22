<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class CheckUserRole
{
    /**
     * ハンドルリクエスト.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure  $next
     * @param  string  $role
     * @return mixed
     */
    public function handle(Request $request, Closure $next, $roleId)
    {
        // ユーザーがログインしているか確認
        if (!Auth::check()) {
            return redirect('/login');
        }

        // ユーザーのロールを確認
        if (Auth::user()->role_id != $roleId) {
            abort(403, 'Unauthorized');
        }
        return $next($request);
    }
}
