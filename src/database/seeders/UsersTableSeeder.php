<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\User;
use Illuminate\Support\Facades\Hash;

class UsersTableSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        User::create([
            'name' => '管理者',
            'email' => '1234@1234',
            'password' => Hash::make('12341234'),
            'role' => 0,//管理者
        ]);

        User::create([
            'name' => 'ユーザー一郎',
            'email' => 'general1@gmail.com',
            'password' => Hash::make('password'),
            'role' => 1,//スタッフ
        ]);

        User::create([
            'name' => 'ユーザー二郎',
            'email' => 'general2@gmail.com',
            'password' => Hash::make('password'),
            'role' => 1,
        ]);

        User::create([
            'name' => 'ユーザー三郎',
            'email' => 'general3@gmail.com',
            'password' => Hash::make('password'),
            'role' => 1,
        ]);

        User::create([
            'name' => 'ユーザー四郎',
            'email' => 'general4@gmail.com',
            'password' => Hash::make('password'),
            'role' => 1,
        ]);

        User::create([
            'name' => 'ユーザー春子',
            'email' => 'general5@gmail.com',
            'password' => Hash::make('password'),
            'role' => 1,
        ]);

        User::create([
            'name' => 'ユーザー夏子',
            'email' => 'general6@gmail.com',
            'password' => Hash::make('password'),
            'role' => 1,
        ]);

        User::create([
            'name' => 'ユーザー秋子',
            'email' => 'general7@gmail.com',
            'password' => Hash::make('password'),
            'role' => 1,
        ]);

        User::create([
            'name' => 'ユーザー冬子',
            'email' => 'general8@gmail.com',
            'password' => Hash::make('password'),
            'role' => 1,
        ]);
    }
}
