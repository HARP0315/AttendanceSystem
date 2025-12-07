<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use Illuminate\Support\Facades\Hash;
use App\Models\User;

class StaffLoginTest extends TestCase
{

    use RefreshDatabase;

    /**
     * 【test】メールアドレスが未入力の場合、バリデーションメッセージが表示される
     * 【test】パスワードが未入力の場合、バリデーションメッセージが表示される
     * 【test】登録内容と一致しない場合、バリデーションメッセージが表示される
     * @return void
     */
    public function test_staff_login_validation_and_failure()
    {
        //【check】 「メールアドレスを入力してください」というバリデーションメッセージが表示される
        $response = $this->post('/login', [
            'email' => '',
            'password' => 'password123',
        ]);

        $response->assertInvalid([
            'email' => 'メールアドレスを入力してください',
        ]);

        //【check】 「パスワードを入力してください」というバリデーションメッセージが表示される
        $response = $this->post('/login', [
            'email' => 'test@example.com',
            'password' => '',
        ]);

        $response->assertInvalid([
            'password' => 'パスワードを入力してください',
        ]);

        //【check】 「ログイン情報が登録されていません」というバリデーションメッセージが表示される
        $user = User::factory()->create([
            'email' => 'test@example.com',
            'password' => Hash::make('correct-password'),
            'role' => '1',
        ]);

        $response = $this->post('/login', [
            'email' => 'test@example.com',
            'password' => 'wrong-password',
        ]);

        $response->assertInvalid([
            'email' => 'ログイン情報が登録されていません',
        ]);
    }
}
