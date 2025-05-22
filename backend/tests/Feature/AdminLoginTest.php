<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use App\Models\User;
use Illuminate\Support\Facades\Hash;

class AdminLoginTest extends TestCase
{
    use RefreshDatabase;

    /**
     * シーディングを有効にするための設定
     *
     * @return bool
     */
    protected function shouldSeed()
    {
        return true;
    }

    /**
     * 管理者ログイン時、メールアドレスが未入力の場合に
     * 「メールアドレスを入力してください」というバリデーションエラーメッセージが表示されることを検証
     */
    public function test_admin_login_requires_email()
    {
        $this->withoutMiddleware(\App\Http\Middleware\VerifyCsrfToken::class);
        User::create([
            'name'     => 'Admin User',
            'email'    => 'admin@example.com',
            'password' => Hash::make('password123'),
            'role_id'  => config('constants.ROLE_ADMIN'),
        ]);

        $loginData = [
            'email'    => '',
            'password' => 'password123',
        ];
        $response = $this->from('/admin/login')->post('/admin/login', $loginData);

        $response->assertRedirect('/admin/login');
        $response->assertSessionHasErrors([
            'email' => 'メールアドレスを入力してください',
        ]);
    }

    /**
     * 管理者ログイン時、パスワードが未入力の場合、バリデーションエラーとして
     * 「パスワードを入力してください」というメッセージが表示されることを検証
     */
    public function test_admin_login_requires_password()
    {
        $this->withoutMiddleware(\App\Http\Middleware\VerifyCsrfToken::class);
        $user = User::create([
            'name'     => 'Admin User',
            'email'    => 'admin@example.com',
            'password' => Hash::make('password123'),
            'role_id'  => config('constants.ROLE_ADMIN'),
        ]);

        $loginData = [
            'email'    => $user->email,
            'password' => '',
        ];
        $response = $this->from('/login')->post('/login', $loginData);

        $response->assertRedirect('/login');
        $response->assertSessionHasErrors([
            'password' => 'パスワードを入力してください',
        ]);
    }
}







