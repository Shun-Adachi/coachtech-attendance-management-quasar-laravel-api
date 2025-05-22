<?php

namespace Tests\Browser;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Dusk\Browser;
use Tests\DuskTestCase;
use App\Models\User;
use Carbon\Carbon;

class DateTimeDisplayTest extends DuskTestCase
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
     * 現在の日時情報がUIと同じ形式で出力されているか検証するテスト
     */
    public function test_current_date_time_displayed()
    {
        $fixedTime = Carbon::create(2025, 2, 13, 14, 30, 0, 'Asia/Tokyo');
        Carbon::setTestNow($fixedTime);
        $user = User::where('email', 'test@example.com')->first();

        $this->browse(function (Browser $browser) use ($user, $fixedTime) {
            $weekdays = ['日', '月', '火', '水', '木', '金', '土'];
            $expectedDate = $fixedTime->year . "年" . $fixedTime->month . "月" . $fixedTime->day . "日(" . $weekdays[$fixedTime->dayOfWeek] . ")";
            $expectedTime = sprintf("%02d:%02d", $fixedTime->hour, $fixedTime->minute);

            $browser->loginAs($user)
                    ->visit('/attendance')
                    ->pause(1500)
                    ->assertInputValue('#currentDate', $expectedDate)
                    ->assertInputValue('#currentTime', $expectedTime);
        });
    }
}
