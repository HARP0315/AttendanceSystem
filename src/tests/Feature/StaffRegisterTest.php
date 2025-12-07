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

    /**
     * 【test】名前が未入力の場合、バリデーションメッセージが表示される
     * 【test】パスワードが未入力の場合、バリデーションメッセージが表示される
     * 【test】パスワードが8文字未満の場合、バリデーションメッセージが表示される
     * 【test】パスワードが一致しない場合、バリデーションメッセージが表示される
     * 【test】フォームに内容が入力されていた場合、データが正常に保存される
     * @return void
     */
    public function test_staff_register_validation_and_success()
    {
        //【check】「お名前を入力してください」というバリデーションメッセージが表示される
        //【check】「メールアドレスを入力してください」というバリデーションメッセージが表示される
        //【check】「パスワードを入力してください」というバリデーションメッセージが表示される
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

        //【check】「パスワードは8文字以上で入力してください」というバリデーションメッセージが表示される
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

        //【check】「パスワードと一致しません」というバリデーションメッセージが表示される
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

        //【check】フォームに内容が入力されていた場合、データが正常に保存される
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

    /**
     * 【test】登録したメールアドレス宛に認証メールが送信されている
     * 【test】メール認証誘導画面で「認証はこちらから」ボタンを押下するとメール認証サイトに遷移する
     * 【test】メール認証サイトのメール認証を完了すると、勤怠登録画面に遷移する
     * @return void
     */
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

        //【check】登録したメールアドレス宛に認証メールが送信されている
        //【check】メール認証サイトに遷移する
        //【check】勤怠登録画面に遷移する
        Notification::assertSentTo($user, VerifyEmail::class);

        $verificationUrl = URL::temporarySignedRoute(
            'verification.verify',
            now()->addMinutes(60),
            ['id' => $user->id, 'hash' => sha1($user->getEmailForVerification())]
        );

        $verificationResponse = $this->actingAs($user)->get($verificationUrl);
        $this->assertTrue($user->fresh()->hasVerifiedEmail());
        $verificationResponse->assertRedirect('/attendance?verified=1');
    }
}
