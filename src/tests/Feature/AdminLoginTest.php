<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use Illuminate\Support\Facades\Hash;
use App\Models\User;

class AdminLoginTest extends TestCase
{

    use RefreshDatabase;

    /**
     * 管理者のログインに関するテスト
     * @return void
     */
    public function test_admin_login_validation_and_failure()
    {
        //【test】メールアドレスが未入力の場合、バリデーションメッセージが表示される
        $response = $this->post('/admin/login', [
            'email' => '',
            'password' => 'password123',
        ]);

        $response->assertInvalid([
            'email' => 'メールアドレスを入力してください',
        ]);

        //【test】パスワードが未入力の場合、バリデーションメッセージが表示される
        $response = $this->post('/login', [
            'email' => 'test@example.com',
            'password' => '',
        ]);

        $response->assertInvalid([
            'password' => 'パスワードを入力してください',
        ]);

        //【test】登録内容と一致しない場合、バリデーションメッセージが表示される
        $user = User::factory()->create([
            'email' => 'test@example.com',
            'password' => Hash::make('correct-password'),
            'role' => '0',
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
