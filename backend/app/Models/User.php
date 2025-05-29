<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use App\Notifications\TwoFactorCodeNotification;
use Laravel\Sanctum\HasApiTokens;

class User extends Authenticatable
{
    use HasApiTokens, HasFactory, Notifiable;

    protected $fillable = [
        'name',
        'email',
        'password',
        'role_id',
        'login_token',
    ];

    public function Attendance()
    {
        return $this->belongsTo(Attendance::class);
    }

    /**
     * 二段階認証コードを発行してメール送信する
     */
    public function sendTwoFactorCode(): void
    {
        // 6桁のランダムコードを生成
        $code = (string) random_int(100000, 999999);

        // モデルに保存（有効期限は10分後）
        $this->two_factor_code = $code;
        $this->two_factor_expires_at = now()->addMinutes(10);
        $this->save();

        // 通知を発行（メール送信）
        $this->notify(new TwoFactorCodeNotification($code));
    }

    /**
     * 入力された二段階認証コードが有効かチェック
     */
    public function validateTwoFactorCode(string $input): bool
    {
        return $this->two_factor_code === $input
            && $this->two_factor_expires_at
            && now()->lt($this->two_factor_expires_at);
    }
}
