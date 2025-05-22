<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class StatusesTableSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        $param = [
            'name' => '出勤前',
        ];
        DB::table('statuses')->insert($param);

        $param = [
            'name' => '勤務中',
        ];
        DB::table('statuses')->insert($param);

        $param = [
            'name' => '休憩中',
        ];
        DB::table('statuses')->insert($param);

        $param = [
            'name' => '退勤済み',
        ];
        DB::table('statuses')->insert($param);

        $param = [
            'name' => '承認待ち',
        ];
        DB::table('statuses')->insert($param);

        $param = [
            'name' => '承認済み',
        ];
        DB::table('statuses')->insert($param);
    }
}
