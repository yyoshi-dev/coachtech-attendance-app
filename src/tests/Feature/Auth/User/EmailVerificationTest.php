<?php

namespace Tests\Feature\Auth\User;

use App\Models\User;
use Illuminate\Auth\Notifications\VerifyEmail;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\URL;
use Tests\TestCase;

class EmailVerificationTest extends TestCase
{
    use RefreshDatabase;

    /**
     * 項目: メール認証機能
     * 内容: 会員登録後、認証メールが送信される
     */
    public function test_verification_email_is_sent_after_user_registration(): void
    {
        // メール送信を擬装
        Notification::fake();

        // 会員登録を行う
        $userData = [
            'name' => 'Test Case',
            'email' => 'test@example.com',
            'password' => 'test1234',
            'password_confirmation' => 'test1234',
        ];

        $this->post('/register', $userData)
            ->assertRedirect(route('verification.notice'));

        // 会員登録が行われた事を確認
        $this->assertDatabaseHas('users', [
            'email' => $userData['email'],
        ]);

        // 登録したメールアドレス宛に認証メールが送信された事を確認
        $user = User::where('email', $userData['email'])->firstOrFail();
        Notification::assertSentTo($user, VerifyEmail::class);
    }

    /**
     * 項目: メール認証機能
     * 内容: メール認証誘導画面で「認証はこちらから」ボタンを押下するとメール認証サイトに遷移する
     */
    public function test_user_can_navigate_to_email_verification_site_from_prompt_screen(): void
    {
        // メール未認証のユーザーを作成
        $user = User::factory()->create([
            'email_verified_at' => null,
        ]);

        // メール認証誘導画面を開く
        $response = $this->actingAs($user)
            ->get(route('verification.notice'));

        // 「認証はこちらから」ボタンがある事を確認
        $response->assertOk()
            ->assertSeeText('認証はこちらから');

        // 「認証はこちらから」ボタンを押し、メール認証サイト (MailHogサイト)に遷移する事を確認
        $this->actingAs($user)
            ->get(route('verification.mailhog'))
            ->assertRedirect(config('services.mailhog.url'));
    }

    /**
     * 項目: メール認証機能
     * 内容: メール認証サイトのメール認証を完了すると、勤怠登録画面に遷移する
     */
    public function test_user_can_access_attendance_registration_view_after_completing_email_verification(): void
    {
        // メール未認証のユーザーを作成
        $user = User::factory()->create([
            'email_verified_at' => null,
        ]);

        // 認証用の署名付きURLを疑似的に作成
        $verificationUrl = URL::temporarySignedRoute(
            'verification.verify',
            now()->addMinutes(60),
            [
                'id' => $user->id,
                'hash' => sha1($user->getEmailForVerification()),
            ]
        );

        // メール認証を完了し、勤怠登録画面に遷移する事を確認
        $this->actingAs($user)
            ->get($verificationUrl)
            ->assertRedirect(route('attendance.index'));

        // DBが更新されている事を確認
        $this->assertNotNull($user->refresh()->email_verified_at);
    }

    /**
     * 項目: メール認証機能
     * 内容: (オプション) メール未認証ユーザーが認証必須画面へアクセスすると、メール認証誘導画面へ飛ばされる
     */
    public function test_unverified_user_is_redirected_to_verification_notice_when_accessing_protected_pages(): void
    {
        // メール未認証のユーザーを作成
        $user = User::factory()->create([
            'email_verified_at' => null,
        ]);

        // 認証必須画面へアクセスすると、メール認証誘導画面にリダイレクトされる事を確認
        $this->actingAs($user)
            ->get(route('attendance.index'))
            ->assertRedirect(route('verification.notice'));
    }

    /**
     * 項目: メール認証機能
     * 内容: (オプション) メール認証誘導画面で「認証メールを再送する」ボタンをクリックすると、認証メールが再送信される
     */
    public function test_verification_email_is_resent_from_verification_notice(): void
    {
        // メール送信を擬装
        Notification::fake();

        // メール未認証のユーザーを作成
        $user = User::factory()->create([
            'email_verified_at' => null,
        ]);

        // メール認証誘導画面を開く
        $response = $this->actingAs($user)
            ->get(route('verification.notice'));

        // 「認証メールを再送する」ボタンがある事を確認
        $response->assertOk()
            ->assertSeeText('認証メールを再送する');

        // 「認証メールを再送する」ボタンを押すと、認証メールが再送される事を確認
        $this->actingAs($user)
            ->post(route('verification.send'))
            ->assertRedirect(route('verification.notice'));

        Notification::assertSentToTimes($user, VerifyEmail::class, 1);
    }

    /**
     * 項目: メール認証機能
     * 内容: (オプション) メール未認証状態でログインした場合、メール認証誘導画面に遷移する
     */
    public function test_unverified_user_is_redirected_to_verification_notice_after_login(): void
    {
        // メール未認証のユーザーを作成
        $user = User::factory()->create([
            'email_verified_at' => null,
            'password' => Hash::make('test1234'),
        ]);

        // ログインすると、メール認証誘導画面に遷移する事を確認
        $this->post('/login', [
                'email' => $user->email,
                'password' => 'test1234',
            ])
            ->assertRedirect(route('verification.notice'));
    }
}
