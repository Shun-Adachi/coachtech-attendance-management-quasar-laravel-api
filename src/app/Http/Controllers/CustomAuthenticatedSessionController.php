<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Http\Requests\UserLoginRequest;
use App\Http\Requests\AdminLoginRequest;
use App\Mail\LoginNotification;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\ValidationException;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;
use Illuminate\Http\Request;

class CustomAuthenticatedSessionController extends Controller
{

    // 一般ユーザーログインフォームを表示
    public function showUserLogin()
    {
        return view('auth.login');
    }

    // 管理者ログインフォームを表示
    public function showAdminLogin()
    {
        return view('auth.admin.login');
    }

    // 一般ユーザーログイン処理
    public function storeUser(UserLoginRequest $request)
    {
        $credentials = $request->only(['email', 'password']);
        $user = User::where('email', $credentials['email'])->first();

        if (!$user || !Auth::validate($credentials)) {
            throw ValidationException::withMessages([
                'password' => ['パスワードが間違っています'],
            ]);
        }

        // 一時トークンを生成
        $token = Str::random(40);
        $user->login_token = $token;
        $user->save();

        // 認証メールを送信
        Mail::to($user->email)->send(new LoginNotification($token));

        return redirect()->route('login')->withInput()->with('message', 'ログインメールを送信しました');
    }

    // 管理者ログイン処理
    public function storeAdmin(AdminLoginRequest $request)
    {
        $credentials = $request->only(['email', 'password']);
        $user = User::where('email', $credentials['email'])->first();

        if (!$user || !Auth::validate($credentials)) {
            throw ValidationException::withMessages([
                'password' => ['パスワードが間違っています'],
            ]);
        }

        // 一時トークンを生成
        $token = Str::random(40);
        $user->login_token = $token;
        $user->save();

        // 認証メールを送信
        Mail::to($user->email)->send(new LoginNotification($token));

        return redirect()->route('admin.login')->withInput()->with('message', 'ログインメールを送信しました');
    }

    // 認証ログイン
    public function verifyLogin(Request $request)
    {
        $token = $request->query('token');
        $user = User::where('login_token', $token)->first();

        if (!$user) {
            return redirect()->back()->withErrors(['error' => 'ログインに失敗しました']);
        }

        // トークンを無効化し、ログイン
        $user->login_token = null;
        $user->save();
        Auth::login($user);
        if ($user->role_id === config('constants.ROLE_ADMIN')) {
            return redirect()->route('admin.attendance.list')->with('message', 'ログインしました');
        } else if ($user->role_id === config('constants.ROLE_USER')) {
            return redirect()->route('admin.login')->with('message', 'ログインしました');
        }
    }

    // ログアウト処理
    public function logout()
    {
        $user = Auth::user();
        Auth::logout();
        if ($user->role_id === config('constants.ROLE_ADMIN')) {
            return redirect()->route('admin.login')->with('message', 'ログアウトしました');
        }
        return redirect()->route('login')->with('message', 'ログアウトしました');
    }
}
