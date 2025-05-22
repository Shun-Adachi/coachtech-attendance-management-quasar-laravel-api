<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class UserLoginTest extends TestCase
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
     * メールアドレスが未入力の場合、バリデーションエラーメッセージ「メールアドレスを入力してください」が表示されることを検証
     */
    public function test_login_requires_email()
    {
        $this->withoutMiddleware(\App\Http\Middleware\VerifyCsrfToken::class);
        $data = [
            'name'                  => 'TestUser',
            'email'                 => 'testuser@example.com',
            'password'              => 'password123',
            'password_confirmation' => 'password123',
            'role_id'               => config('constants.ROLE_USER'),
        ];
        $response = $this->post('/register', $data);

        $loginData = [
            'email'    => '',
            'password' => 'password123',
        ];
        $response = $this->from('/login')->post('/login', $loginData);

        $response->assertRedirect('/login');
        $response->assertSessionHasErrors([
            'email' => 'メールアドレスを入力してください',
        ]);
    }

    /**
     * パスワードが未入力の場合、バリデーションエラーとして
     * 「パスワードを入力してください」というメッセージが表示されることを検証
     */
    public function test_login_requires_password()
    {
        $this->withoutMiddleware(\App\Http\Middleware\VerifyCsrfToken::class);
        $data = [
            'name'                  => 'TestUser',
            'email'                 => 'testuser@example.com',
            'password'              => 'password123',
            'password_confirmation' => 'password123',
            'role_id'               => config('constants.ROLE_USER'),
        ];
        $response = $this->post('/register', $data);

        $loginData = [
            'email'    => $data['email'],
            'password' => '',
        ];
        $response = $this->from('/login')->post('/login', $loginData);

        $response->assertRedirect('/login');
        $response->assertSessionHasErrors([
            'password' => 'パスワードを入力してください',
        ]);
    }

    /**
     * 登録内容と一致しない場合、バリデーションエラーとして
     * 「ログイン情報が登録されていません」というメッセージが表示されることを検証
     */
    public function test_login_fails_when_registration_details_do_not_match()
    {
        $this->withoutMiddleware(\App\Http\Middleware\VerifyCsrfToken::class);
        $data = [
            'name'                  => 'TestUser',
            'email'                 => 'testuser@example.com',
            'password'              => 'password123',
            'password_confirmation' => 'password123',
            'role_id'               => config('constants.ROLE_USER'),
        ];
        $this->post('/register', $data);

        $loginData = [
            'email'    => 'wrong@example.com',
            'password' => 'password123',
        ];
        $response = $this->from('/login')->post('/login', $loginData);

        $response->assertRedirect('/login');
        $response->assertSessionHasErrors([
            'email' => 'ログイン情報が登録されていません',
        ]);
    }

}
