<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;
use App\Models\User;

class RegistrationTest extends TestCase
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
     * 名前が未入力の場合、バリデーションエラーとなり「お名前を入力してください」というメッセージが返されることを検証
     */
    public function test_registration_requires_name()
    {
        $this->withoutMiddleware(\App\Http\Middleware\VerifyCsrfToken::class);
        $data = [
            'name'                  => '',
            'email'                 => 'user@example.com',
            'password'              => 'password123',
            'password_confirmation' => 'password123',
        ];
        $response = $this->from('/register')->post('/register', $data);
        $response->assertRedirect('/register');
        $response->assertSessionHasErrors([
            'name' => 'お名前を入力してください',
        ]);
    }

    /**
     * メールアドレスが未入力の場合、バリデーションエラーとなり
     * 「メールアドレスを入力してください」というメッセージが返されることを検証
     */
    public function test_registration_requires_email()
    {
        $this->withoutMiddleware(\App\Http\Middleware\VerifyCsrfToken::class);
        $data = [
            'name'                  => 'テストユーザー',
            'email'                 => '',
            'password'              => 'password123',
            'password_confirmation' => 'password123',
        ];
        $response = $this->from('/register')->post('/register', $data);
        $response->assertRedirect('/register');
        $response->assertSessionHasErrors([
            'email' => 'メールアドレスを入力してください',
        ]);
    }

    /**
     * パスワードが8文字未満の場合、バリデーションエラーとして
     * 「パスワードは8文字以上で入力してください」というメッセージが返されることを検証
     */
    public function test_registration_requires_password_minimum_length()
    {
        $this->withoutMiddleware(\App\Http\Middleware\VerifyCsrfToken::class);
        $data = [
            'name'                  => 'テストユーザー',
            'email'                 => 'user@example.com',
            'password'              => '1234567',
            'password_confirmation' => '1234567',
        ];

        $response = $this->from('/register')->post('/register', $data);
        $response->assertRedirect('/register');
        $response->assertSessionHasErrors([
            'password' => 'パスワードは8文字以上で入力してください',
        ]);
    }

    /**
     * パスワードと確認用パスワードが一致しない場合、バリデーションエラーとなり
     * 「パスワードと一致しません」というメッセージが返されることを検証
     */
    public function test_registration_fails_when_password_confirmation_does_not_match()
    {
        $this->withoutMiddleware(\App\Http\Middleware\VerifyCsrfToken::class);
        $data = [
            'name'                  => 'テストユーザー',
            'email'                 => 'user@example.com',
            'password'              => 'password123',
            'password_confirmation' => 'differentpassword',
        ];

        $response = $this->from('/register')->post('/register', $data);
        $response->assertRedirect('/register');
        $response->assertSessionHasErrors([
            'password' => 'パスワードと一致しません',
        ]);
    }

    /**
     * パスワードが未入力の場合、バリデーションエラーとして
     * 「パスワードを入力してください」というメッセージが返されることを検証
     */
    public function test_registration_requires_password()
    {
        $this->withoutMiddleware(\App\Http\Middleware\VerifyCsrfToken::class);
        $data = [
            'name'  => 'テストユーザー',
            'email' => 'user@example.com',
        ];

        $response = $this->from('/register')->post('/register', $data);
        $response->assertRedirect('/register');
        $response->assertSessionHasErrors([
            'password' => 'パスワードを入力してください',
        ]);
    }

    /**
     * データベースに登録したユーザー情報が保存されることを検証
     */
    public function test_registration_saves_user_data()
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
        $response->assertRedirect('/attendance');
        $this->assertDatabaseHas('users', [
            'email'   => 'testuser@example.com',
            'name'    => 'TestUser',
            'role_id' => config('constants.ROLE_USER'),
        ]);

        $user = User::where('email', 'testuser@example.com')->first();
        $this->assertNotNull($user);
        $this->assertTrue(Hash::check('password123', $user->password));
    }

    /**
     * 登録内容と一致しない場合、バリデーションエラーとして
     * 「ログイン情報が登録されていません」というメッセージが表示されることを検証
     */
    public function test_admin_login_fails_when_details_do_not_match()
    {
        $this->withoutMiddleware(\App\Http\Middleware\VerifyCsrfToken::class);
        User::create([
            'name'     => 'Admin User',
            'email'    => 'admin@example.com',
            'password' => Hash::make('password123'),
            'role_id'  => config('constants.ROLE_ADMIN'),
        ]);

        $loginData = [
            'email'    => 'wrong@example.com',
            'password' => 'password123',
        ];
        $response = $this->from('/admin/login')->post('/admin/login', $loginData);

        $response->assertRedirect('/admin/login');
        $response->assertSessionHasErrors([
            'email' => 'ログイン情報が登録されていません',
        ]);
    }
}
