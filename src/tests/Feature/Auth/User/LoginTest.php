<?php

namespace Tests\Feature\Auth\User;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class LoginTest extends TestCase
{
    use RefreshDatabase;

    protected $user;

    protected function setUp(): void
    {
        parent::setUp();

        // ユーザー情報を登録する
        $this->user = User::factory()->create([
            'name' => 'テストユーザー',
            'email' => 'test_user@example.com',
            'password' => Hash::make('test1234'),
            'role' => 'user',
        ]);
    }

    /**
     * 項目: ログイン認証機能 (一般ユーザー)
     * 内容: メールアドレスが未入力の場合、バリデーションメッセージが表示される
     */
    public function test_email_field_is_required(): void
    {
        // メールアドレス以外のユーザー情報を入力
        $userData = [
            'email' => '',
            'password' => 'test1234',
        ];

        // ログインしてバリデーションエラーを確認
        $this->post('/login', $userData)
            ->assertSessionHasErrors([
                'email' => 'メールアドレスを入力してください'
            ]);
    }

    /**
     * 項目: ログイン認証機能 (一般ユーザー)
     * 内容: パスワードが未入力の場合、バリデーションメッセージが表示される
     */
    public function test_password_field_is_required(): void
    {
        // パスワード以外のユーザー情報を入力
        $userData = [
            'email' => 'test_user@example.com',
            'password' => '',
        ];

        // ログインしてバリデーションエラーを確認
        $this->post('/login', $userData)
            ->assertSessionHasErrors([
                'password' => 'パスワードを入力してください'
            ]);
    }

    /**
     * 項目: ログイン認証機能 (一般ユーザー)
     * 内容: 登録内容と一致しない場合、バリデーションメッセージが表示される
     */
    public function test_validation_error_occurs_when_input_is_wrong(): void
    {
        // 誤ったメールアドレスのユーザー情報を入力
        $userData = [
            'email' => 'wrong_test_user@example.com',
            'password' => 'test1234',
        ];

        // ログインしてバリデーションエラーを確認
        $this->post('/login', $userData)
            ->assertSessionHasErrors([
                'password' => 'ログイン情報が登録されていません'
            ]);
    }

    /**
     * 項目: ログイン認証機能 (一般ユーザー)
     * 内容: (オプション) メールアドレスがメール形式でない場合、バリデーションメッセージが表示される
     */
    public function test_email_should_be_in_email_format(): void
    {
        // メールアドレスをメール形式とせずにユーザー情報を入力
        $userData = [
            'email' => 'test_user',
            'password' => 'test1234',
        ];

        // ログインしてバリデーションエラーを確認
        $this->post('/login', $userData)
            ->assertSessionHasErrors([
                'email' => 'メールアドレスはメール形式で入力してください'
            ]);
    }

    /**
     * 項目: ログイン認証機能 (一般ユーザー)
     * 内容: (オプション) 正しい情報が入力された場合、ログイン処理が実行される
     */
    public function test_user_can_login(): void
    {
        // 正しいユーザー情報を入力
        $userData = [
            'email' => 'test_user@example.com',
            'password' => 'test1234',
        ];

        // ログイン処理を実行し、勤怠登録画面に遷移する事を確認
        $this->post('/login', $userData)
            ->assertRedirect(route('attendance.index'));

        // ログイン状態になっている事を確認
        $this->assertAuthenticatedAs($this->user);
    }
}
