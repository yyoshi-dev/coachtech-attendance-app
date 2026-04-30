<?php

namespace Tests\Feature\User;

use App\Models\Attendance;
use App\Models\AttendanceBreak;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AttendanceDetailTest extends TestCase
{
    use RefreshDatabase;

    protected User $user;
    protected Carbon $workDate;

    protected function setUp(): void
    {
        parent::setUp();

        $this->user = User::factory()->create([
            'name' => 'Test User'
        ]);

        $this->workDate = Carbon::parse('2026-04-15');
    }

    /**
     * 項目: 勤怠詳細情報取得機能 (一般ユーザー)
     * 内容: 勤怠詳細画面の「名前」がログインユーザーの氏名になっている
     */
    public function test_displayed_name_matches_login_user_name(): void
    {
        // 勤怠を作成
        $attendance = Attendance::factory()
            ->for($this->user)
            ->forWorkDate($this->workDate)
            ->create();

        // ログインして勤怠詳細ページを開く
        $response = $this->actingAs($this->user)
            ->get(route('attendance.detail', ['id' => $attendance->id]));

        $response->assertOk();

        // 名前がログインユーザーの名前になっている事を確認
        $response->assertSeeInOrder([
            'data-testid="user-name"',
            $this->user->name,
        ], false);
    }

    /**
     * 項目: 勤怠詳細情報取得機能 (一般ユーザー)
     * 内容: 勤怠詳細画面の「日付」が選択した日付になっている
     */
    public function test_displayed_date_matches_selected_date(): void
    {
        // 勤怠を作成
        $attendance = Attendance::factory()
            ->for($this->user)
            ->forWorkDate($this->workDate)
            ->create();

        // ログインして勤怠詳細ページを開く
        $response = $this->actingAs($this->user)
            ->get(route('attendance.detail', ['id' => $attendance->id]));

        $response->assertOk();

        // 日付が選択した日付になっているかを確認
        $response->assertSeeInOrder([
            'data-testid="work-date-year"',
            $attendance->work_date->format('Y') . '年',
        ], false);
        $response->assertSeeInOrder([
            'data-testid="work-date-month-day"',
            $attendance->work_date->format('n月j日'),
        ], false);
    }

    /**
     * 項目: 勤怠詳細情報取得機能 (一般ユーザー)
     * 内容: 「出勤・退勤」にて記されている時間がログインユーザーの打刻と一致している
     */
    public function test_displayed_clock_in_and_out_match_attendance_record(): void
    {
        // 出退勤時間を定義
        $clockIn = $this->workDate->copy()->setTime(9, 12, 00);
        $clockOut = $this->workDate->copy()->setTime(19, 15, 00);

        // 勤怠を作成
        $attendance = Attendance::factory()
            ->for($this->user)
            ->forWorkDate($this->workDate)
            ->create([
                'clock_in' => $clockIn,
                'clock_out' => $clockOut,
            ]);

        // ログインして勤怠詳細ページを開く
        $response = $this->actingAs($this->user)
            ->get(route('attendance.detail', ['id' => $attendance->id]));

        $response->assertOk();

        // 出退勤時間が打刻時間と一致している事を確認
        $response->assertSeeInOrder([
            'data-testid="requested-clock-in-input"',
            $attendance->clock_in->format('H:i'),
        ], false);
        $response->assertSeeInOrder([
            'data-testid="requested-clock-out-input"',
            $attendance->clock_out->format('H:i'),
        ], false);
    }

    /**
     * 項目: 勤怠詳細情報取得機能 (一般ユーザー)
     * 内容: 「休憩」にて記されている時間がログインユーザーの打刻と一致している
     */
    public function test_displayed_break_time_matches_attendance_record(): void
    {
        // 出退勤時間・休憩時間を定義
        $clockIn = $this->workDate->copy()->setTime(9, 00, 00);
        $clockOut = $this->workDate->copy()->setTime(19, 00, 00);
        $breakStart = $this->workDate->copy()->setTime(12, 00, 00);
        $breakEnd = $this->workDate->copy()->setTime(13, 00, 00);
        $secondBreakStart = $this->workDate->copy()->setTime(15, 00, 00);
        $secondBreakEnd = $this->workDate->copy()->setTime(15, 15, 00);

        // 勤怠を作成
        $attendance = Attendance::factory()
            ->for($this->user)
            ->forWorkDate($this->workDate)
            ->create([
                'clock_in' => $clockIn,
                'clock_out' => $clockOut,
            ]);

        // 休憩を作成
        $breaks = AttendanceBreak::factory()
            ->count(2)
            ->for($attendance)
            ->sequence(
                [
                    'break_start' => $breakStart,
                    'break_end' => $breakEnd,
                    'sort_order' => 1,
                ],
                [
                    'break_start' => $secondBreakStart,
                    'break_end' => $secondBreakEnd,
                    'sort_order' => 2
                ]
            )
            ->create();

        // ログインして勤怠詳細ページを開く
        $response = $this->actingAs($this->user)
            ->get(route('attendance.detail', ['id' => $attendance->id]));

        $response->assertOk();

        // 休憩時間が打刻時間と一致している事を確認
        foreach ($breaks as $index => $break) {
            $response->assertSeeInOrder([
                'data-testid="requested-break-start-input-' . $index . '"',
                $break->break_start->format('H:i'),
            ], false);
            $response->assertSeeInOrder([
                'data-testid="requested-break-end-input-' . $index . '"',
                $break->break_end->format('H:i'),
            ], false);
        }
    }

    /**
     * 項目: 勤怠詳細情報取得機能 (一般ユーザー)
     * 内容: (オプション) 勤怠詳細画面の「備考」が対象勤怠の備考と一致している
     */
    public function test_displayed_remarks_matches_attendance_record(): void
    {
        // 勤怠を作成
        $attendance = Attendance::factory()
            ->for($this->user)
            ->forWorkDate($this->workDate)
            ->create(['remarks' => 'TEST']);

        // ログインして勤怠詳細ページを開く
        $response = $this->actingAs($this->user)
            ->get(route('attendance.detail', ['id' => $attendance->id]));

        $response->assertOk();

        // 備考が勤怠の備考になっている事を確認
        $response->assertSeeInOrder([
            'data-testid="request-remarks-textarea"',
            $attendance->remarks,
        ], false);
    }
}
