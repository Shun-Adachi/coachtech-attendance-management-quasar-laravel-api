<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Validator;
use App\Models\User;

class AdminLoginRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     *
     * @return bool
     */
    public function authorize()
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array
     */
    public function rules()
    {
        return
            [
                'email' => ['required', 'string', 'email', 'exists:users,email'],
                'password' => ['required', 'string', 'min:8'],
            ];
    }

    /**
     * カスタムバリデーションを追加
     */
    public function withValidator(Validator $validator)
    {
        $validator->after(function ($validator) {
            $user = User::where('email', $this->email)->first();

            // ユーザーが存在しない場合
            if (!$user) {
                $validator->errors()->add('email', 'ログイン情報が登録されていません');
                return;
            }

            // 管理者以外
            if ($user->role_id !== config('constants.ROLE_ADMIN')) {
                $validator->errors()->add('email', 'ログイン情報が登録されていません');
            }
        });
    }

    public function messages()
    {
        return [
            'email.required' => 'メールアドレスを入力してください',
            'email.email' => 'ユーザー名@ドメイン形式で入力してください',
            'email.exists' => 'ログイン情報が登録されていません',
            'password.required' => 'パスワードを入力してください',
            'password.min' => 'パスワードは8文字以上で入力してください',
        ];
    }
}
