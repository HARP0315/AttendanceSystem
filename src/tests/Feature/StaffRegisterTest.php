<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\URL;
use Illuminate\Auth\Notifications\VerifyEmail;
use App\Models\User;

class StaffRegisterTest extends TestCase
{
    use RefreshDatabase;


    public function test_staff_register_validation_and_success()
    {
        //【test】名前が未入力の場合、バリデーションメッセージが表示される
        //【test】パスワードが未入力の場合、バリデーションメッセージが表示される
        $response = $this->post('/register', [
            'name' => '',
            'email' => '',
            'password' => '',
            'password_confirmation' => 'password123',
            'role' => '1',
        ]);

        $response->assertInvalid([
            'name' => 'お名前を入力してください',
            'email' => 'メールアドレスを入力してください',
            'password' => 'パスワードを入力してください',
        ]);

        //【test】パスワードが8文字未満の場合、バリデーションメッセージが表示される
        $response = $this->post('/register', [
            'name' => 'テストユーザー',
            'email' => 'test1@example.com',
            'password' => '1234567',
            'password_confirmation' => '1234567',
            'role' => '1',
        ]);

        $response->assertInvalid([
            'password' => 'パスワードは8文字以上で入力してください',
        ]);

        //【test】パスワードが一致しない場合、バリデーションメッセージが表示される
        $response = $this->post('/register', [
            'name' => 'テストユーザー',
            'email' => 'test2@example.com',
            'password' => 'password123',
            'password_confirmation' => 'different_password',
            'role' => '1',
        ]);

        $response->assertInvalid([
            'password' => 'パスワードと一致しません',
        ]);

        //【test】フォームに内容が入力されていた場合、データが正常に保存される
        $validData = [
            'name' => 'テスト太郎',
            'email' => 'success@example.com',
            'password' => 'password123',
            'password_confirmation' => 'password123',
            'role' => '1',
        ];

        $response = $this->post('/register', $validData);
        $response->assertValid();

        $this->assertDatabaseHas('users', [
            'email' => 'success@example.com',
            'name' => 'テスト太郎',
        ]);

    }

    public function test_staff_register_email_verification()
    {
        Notification::fake();

        $userData = [
            'name' => 'テスト太郎',
            'email' => 'success@example.com',
            'password' => 'password123',
            'password_confirmation' => 'password123',
        ];

        $response = $this->post('/register', $userData);
        $response->assertValid();

        $user = User::where('email', $userData['email'])->first();
        $this->assertNotNull($user);

        //【test】登録したメールアドレス宛に認証メールが送信されている
        Notification::assertSentTo($user, VerifyEmail::class);

        //メール認証リンクを作成
        $verificationUrl = URL::temporarySignedRoute(
            'verification.verify',
            now()->addMinutes(60),
            ['id' => $user->id, 'hash' => sha1($user->getEmailForVerification())]
        );

        //認証URLにアクセス（ログイン状態で）
        $verificationResponse = $this->actingAs($user)->get($verificationUrl);

        //メール認証が完了していることを確認
        $this->assertTrue($user->fresh()->hasVerifiedEmail());

        //【test】メール認証サイトのメール認証を完了すると、勤怠登録画面に遷移する
        $verificationResponse->assertRedirect('/attendance?verified=1');
    }


}
