<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\DB;

class UsersTableSeeder extends Seeder
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
            'email' => 'root@example.com',
            'password' => Hash::make('rootroot'),
            'role_id' => '1',
        ];
        DB::table('users')->insert($param);

        $param = [
            'name' => 'テスト五郎',
            'email' => 'test@example.com',
            'password' => Hash::make('testtest'),
            'role_id' => '2',
        ];
        DB::table('users')->insert($param);

        $param = [
            'name' => 'hoge',
            'email' => 'hoge@example.com',
            'password' => Hash::make('hogehoge'),
            'role_id' => '2',
        ];
        DB::table('users')->insert($param);
    }
}
