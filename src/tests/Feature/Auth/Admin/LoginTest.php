<?php

namespace Tests\Feature\Auth\Admin;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class LoginTest extends TestCase
{
    use RefreshDatabase;

    protected User $admin;

    protected function setUp(): void
    {
        parent::setUp();

        $this->admin = User::factory()->create([
            'name' => 'テスト管理者',
            'email' => 'test_admin@example.com',
            'password' => Hash::make('test1234'),
            'role' => 'admin',
        ]);
    }

    /**
     * 項目: ログイン認証機能 (管理者)
     * 内容: メールアドレスが未入力の場合、バリデーションメッセージが表示される
     */
    public function test_email_field_is_required(): void
    {
        // メールアドレス以外のユーザー情報を入力
        $userData = [
            'email' => '',
            'password' => 'test1234',
            'login_type' => 'admin',
        ];

        // ログインしてバリデーションエラーを確認
        $this->post('/login', $userData)
            ->assertSessionHasErrors([
                'email' => 'メールアドレスを入力してください'
            ]);
    }

    /**
     * 項目: ログイン認証機能 (管理者)
     * 内容: パスワードが未入力の場合、バリデーションメッセージが表示される
     */
    public function test_password_field_is_required(): void
    {
        // パスワード以外のユーザー情報を入力
        $userData = [
            'email' => 'test_admin@example.com',
            'password' => '',
            'login_type' => 'admin',
        ];

        // ログインしてバリデーションエラーを確認
        $this->post('/login', $userData)
            ->assertSessionHasErrors([
                'password' => 'パスワードを入力してください'
            ]);
    }

    /**
     * 項目: ログイン認証機能 (管理者)
     * 内容: 登録内容と一致しない場合、バリデーションメッセージが表示される
     */
    public function test_validation_error_occurs_when_input_is_wrong(): void
    {
        // 誤ったメールアドレスのユーザー情報を入力
        $userData = [
            'email' => 'wrong_test_admin@example.com',
            'password' => 'test1234',
            'login_type' => 'admin',
        ];

        // ログインしてバリデーションエラーを確認
        $this->post('/login', $userData)
            ->assertSessionHasErrors([
                'password' => 'ログイン情報が登録されていません'
            ]);
    }

    /**
     * 項目: ログイン認証機能 (管理者)
     * 内容: (オプション) メールアドレスがメール形式でない場合、バリデーションメッセージが表示される
     */
    public function test_email_should_be_in_email_format(): void
    {
        // メールアドレスをメール形式とせずにユーザー情報を入力
        $userData = [
            'email' => 'test_admin',
            'password' => 'test1234',
            'login_type' => 'admin',
        ];

        // ログインしてバリデーションエラーを確認
        $this->post('/login', $userData)
            ->assertSessionHasErrors([
                'email' => 'メールアドレスはメール形式で入力してください'
            ]);
    }

    /**
     * 項目: ログイン認証機能 (管理者)
     * 内容: (オプション) 正しい情報が入力された場合、ログイン処理が実行される
     */
    public function test_admin_can_login(): void
    {
        // 正しいユーザー情報を入力
        $userData = [
            'email' => 'test_admin@example.com',
            'password' => 'test1234',
            'login_type' => 'admin',
        ];

        // ログイン処理を実行し、日次勤怠一覧画面に遷移する事を確認
        $this->post('/login', $userData)
            ->assertRedirect(route('admin.attendance.list'));

        // ログイン状態になっている事を確認
        $this->assertAuthenticatedAs($this->admin);
    }
}
