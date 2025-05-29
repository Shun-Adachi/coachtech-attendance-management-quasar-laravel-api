<?php

namespace App\Http\Controllers\Api\User;


use App\Http\Controllers\Controller;
use App\Models\User;
use App\Http\Requests\RegisterRequest;
use App\Http\Requests\UserLoginRequest;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Hash;

class AuthController extends Controller
{
    /**
     * API 会員登録
     * POST /api/register
     */
    public function register(RegisterRequest $request): JsonResponse
    {
        // バリデーション済みデータ取得
        $data = $request->validated();

        // ユーザー作成
        User::create([
            'name'     => $data['name'],
            'email'    => $data['email'],
            'password' => Hash::make($data['password']),
            'role_id' => config('constants.ROLE_USER'),
        ]);

        return response()->json([
            'message' => '登録が完了しました。'
        ], 201);
    }

    public function login(UserLoginRequest $request): JsonResponse
    {
        $credentials = $request->only('email', 'password');
        if (! Auth::attempt($credentials)) {
            return response()->json(['message' => '認証失敗'], 401);
        }

        // ここで必ず２段階認証コードを発行・送信
        $user = Auth::user();
        $user->sendTwoFactorCode(); // 例: カスタムメソッドでメールorSMS送信

        // トークンは発行せず、必ず step2 へ誘導
        return response()->json([
            'two_factor_required' => true,
            'user_id'             => $user->id,
        ], 200);
    }

    public function login2fa(Request $request)
    {
        $request->validate([
            'user_id' => 'required|exists:users,id',
            'code'    => 'required|string',
        ]);

        $user = User::findOrFail($request->user_id);
        if (! $user->validateTwoFactorCode($request->code)) {
            return response()->json(['message' => '無効な認証コードです'], 422);
        }

        // 認証コードが正しければトークンを発行
        //Auth::login($user);
        //$request->session()->regenerate();

        $token = $user->createToken('CoachtechAttendanceManagement')->plainTextToken;

        return response()->json([
            'message' => '2段階認証に成功しました',
            'token'   => $token
        ], 200);
    }
}
