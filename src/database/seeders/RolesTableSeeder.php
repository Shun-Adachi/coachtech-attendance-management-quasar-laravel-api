<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class RolesTableSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        $param = [
            'name' => '管理者',
        ];
        DB::table('roles')->insert($param);

        $param = [
            'name' => '一般ユーザー',
        ];
        DB::table('roles')->insert($param);
    }
}
