<?php

namespace App\Http\Controllers\Api\User;


use App\Http\Controllers\Controller;
use App\Models\User;
use App\Http\Requests\RegisterRequest;
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
}
