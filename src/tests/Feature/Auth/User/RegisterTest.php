<?php

namespace Tests\Feature\Auth\User;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class RegisterTest extends TestCase
{
    use RefreshDatabase;

    /**
     * 共通の登録リクエスト送信メソッド
     */
    private function postRegistration(array $overrides = []) {
        $default = [
            'name' => 'テスト太郎',
            'email' => 'test@example.com',
            'password' => 'test1234',
            'password_confirmation' => 'test1234',
        ];

        return $this->post('/register', array_merge($default, $overrides));
    }

    /**
     * 項目: 認証機能 (一般ユーザー)
     * 内容: 名前が未入力の場合、バリデーションメッセージが表示される
     */
    public function test_name_field_is_required(): void
    {
        // 名前以外のユーザー情報を入力し、会員登録を実施
        $response = $this->postRegistration([
            'name' => ''
        ]);

        // バリデーションメッセージを確認
        $response->assertSessionHasErrors([
            'name' => 'お名前を入力してください'
        ]);

        // DBにユーザーが登録されていない事を確認
        $this->assertDatabaseCount('users', 0);
    }

    /**
     * 項目: 認証機能 (一般ユーザー)
     * 内容: メールアドレスが未入力の場合、バリデーションメッセージが表示される
     */
    public function test_email_field_is_required(): void
    {
        // メールアドレス以外のユーザー情報を入力し、会員登録を実施
        $response = $this->postRegistration([
            'email' => ''
        ]);

        // バリデーションメッセージの確認
        $response->assertSessionHasErrors([
            'email' => 'メールアドレスを入力してください'
        ]);

        // DBにユーザーが登録されていない事を確認
        $this->assertDatabaseCount('users', 0);
    }

    /**
     * 項目: 認証機能 (一般ユーザー)
     * 内容: (オプション) メールアドレスがメール形式でない場合、バリデーションメッセージが表示される
     */
    public function test_email_should_be_in_email_format(): void
    {
        // メールアドレスをメール形式にせずにユーザー情報を入力し、会員登録を実施
        $response = $this->postRegistration([
            'email' => 'test'
        ]);

        // バリデーションメッセージの確認
        $response->assertSessionHasErrors([
            'email' => 'メールアドレスはメール形式で入力してください'
        ]);

        // DBにユーザーが登録されていない事を確認
        $this->assertDatabaseCount('users', 0);
    }

    /**
     * 項目: 認証機能 (一般ユーザー)
     * 内容: パスワードが8文字未満の場合、バリデーションメッセージが表示される
     */
    public function test_validation_error_occurs_when_password_is_too_short(): void
    {
        // パスワードを8文字未満にしてユーザー情報を入力し、会員登録を実施
        $response = $this->postRegistration([
            'password' => '1234567',
            'password_confirmation' => '1234567'
        ]);

        // バリデーションメッセージの確認
        $response->assertSessionHasErrors([
            'password' => 'パスワードは8文字以上で入力してください'
        ]);

        // DBにユーザーが登録されていない事を確認
        $this->assertDatabaseCount('users', 0);
    }

    /**
     * 項目: 認証機能 (一般ユーザー)
     * 内容: パスワードが一致しない場合、バリデーションメッセージが表示される
     */
    public function test_validation_error_occurs_when_password_confirmation_does_not_match(): void
    {
        // パスワードを一致させずにユーザー情報を入力し、会員登録を実施
        $response = $this->postRegistration([
            'password' => 'test1234',
            'password_confirmation' => 'test4321'
        ]);

        // バリデーションメッセージの確認
        $response->assertSessionHasErrors([
            'password_confirmation' => 'パスワードと一致しません'
        ]);

        // DBにユーザーが登録されていない事を確認
        $this->assertDatabaseCount('users', 0);
    }

    /**
     * 項目: 認証機能 (一般ユーザー)
     * 内容: パスワードが未入力の場合、バリデーションメッセージが表示される
     */
    public function test_password_field_is_required(): void
    {
        // パスワード以外のユーザー情報を入力し、会員登録を実施
        $response = $this->postRegistration([
            'password' => '',
            'password_confirmation' => '',
        ]);

        // バリデーションメッセージの確認
        $response->assertSessionHasErrors([
            'password' => 'パスワードを入力してください'
        ]);

        // DBにユーザーが登録されていない事を確認
        $this->assertDatabaseCount('users', 0);
    }

    /**
     * 項目: 認証機能 (一般ユーザー)
     * 内容: フォームに内容が入力されていた場合、データが正常に保存される
     */
    public function test_user_is_registered(): void
    {
        // ユーザー情報を入力し、会員登録を実施
        $userData = [
            'name' => 'テスト太郎',
            'email' => 'success@example.com',
            'password' => 'test1234',
            'password_confirmation' => 'test1234'
        ];
        $this->postRegistration($userData)
            ->assertRedirect(route('verification.notice'));

        // DBにユーザーが登録されている事を確認
        $this->assertDatabaseHas('users', [
            'email' => 'success@example.com'
        ]);
    }
}
