<?php

namespace Tests\Feature\User;

use App\Models\Attendance;
use App\Models\AttendanceBreak;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AttendanceListTest extends TestCase
{
    use RefreshDatabase;

    protected User $user;

    protected function setUp(): void
    {
        parent::setUp();

        Carbon::setTestNow('2026-03-17 00:00:00');
        $this->user = User::factory()->create();
    }

    protected function tearDown(): void
    {
        Carbon::setTestNow();
        parent::tearDown();
    }

    /**
     * 項目: 勤怠一覧情報取得機能 (一般ユーザー)
     * 内容: 自分が行った勤怠情報が全て表示されている
     */
    public function test_user_can_view_all_own_attendance(): void
    {
        // 勤怠用の日付リストの作成
        $start = now()->startOfMonth();
        $end = $start->copy()->addDays(15);
        $dates = $start->daysUntil($end);

        // 勤怠の作成
        $attendances = collect();

        foreach ($dates as $date) {
            $attendance = Attendance::factory()
                ->for($this->user)
                ->forWorkDate($date)
                ->create();

            AttendanceBreak::factory()
                ->forAttendance($attendance, 1)
                ->create();

            $attendance->refresh();
            $attendances->push($attendance);
        }

        // ログインして勤怠一覧ページを開く
        $response = $this->actingAs($this->user)
            ->get(route('attendance.list'));

        $response->assertOk();

        // 自分の勤怠情報が全て表示されている事を確認
        foreach ($attendances as $attendance) {
            $response->assertSeeInOrder([
                Carbon::parse($attendance->work_date)->isoFormat('MM/DD(ddd)'),
                Carbon::parse($attendance->clock_in)->format('H:i'),
                Carbon::parse($attendance->clock_out)->format('H:i'),
                $attendance->breakTotalFormatted,
                $attendance->workTotalFormatted,
            ]);
        }
    }

    /**
     * 項目: 勤怠一覧情報取得機能 (一般ユーザー)
     * 内容: 勤怠一覧画面に遷移した際に現在の月が表示される
     */
    public function test_current_month_is_displayed(): void
    {
        // 前月・当月・翌月の日付を準備
        $currentMonth = now()->startOfMonth();
        $previousMonth = $currentMonth->copy()->subMonthNoOverflow();
        $nextMonth = $currentMonth->copy()->addMonthNoOverflow();

        // 前月・当月・翌月の勤怠を1件ずつ作成
        $currentAttendance = Attendance::factory()
            ->for($this->user)
            ->forWorkDate($currentMonth)
            ->create();

        $previousAttendance = Attendance::factory()
            ->for($this->user)
            ->forWorkDate($previousMonth)
            ->create();

        $nextAttendance = Attendance::factory()
            ->for($this->user)
            ->forWorkDate($nextMonth)
            ->create();

        // ログインして勤怠一覧ページを開く
        $response = $this->actingAs($this->user)
            ->get(route('attendance.list'));

        $response->assertOk();

        // 現在の月が表示されている事を確認
        $response->assertSeeText(now()->format('Y/m'));

        // 当月のデータのみ表示されている事を確認
        $response->assertSeeText(
            Carbon::parse($currentAttendance->work_date)->isoFormat('MM/DD(ddd)')
        );

        $response->assertDontSeeText(
            Carbon::parse($previousAttendance->work_date)->isoFormat('MM/DD(ddd)')
        );

        $response->assertDontSeeText(
            Carbon::parse($nextAttendance->work_date)->isoFormat('MM/DD(ddd)')
        );
    }

    /**
     * 項目: 勤怠一覧情報取得機能 (一般ユーザー)
     * 内容: 「前月」を押下した時に表示月の前月の情報が表示される
     */
    public function test_previous_month_is_displayed(): void
    {
        // 前月・当月・翌月の日付を準備
        $currentMonth = now()->startOfMonth();
        $previousMonth = $currentMonth->copy()->subMonthNoOverflow();
        $nextMonth = $currentMonth->copy()->addMonthNoOverflow();

        // 前月・当月・翌月の勤怠を1件ずつ作成
        $currentAttendance = Attendance::factory()
            ->for($this->user)
            ->forWorkDate($currentMonth)
            ->create();

        $previousAttendance = Attendance::factory()
            ->for($this->user)
            ->forWorkDate($previousMonth)
            ->create();

        $nextAttendance = Attendance::factory()
            ->for($this->user)
            ->forWorkDate($nextMonth)
            ->create();

        // ログインして勤怠一覧ページを開く
        $this->actingAs($this->user)
            ->get(route('attendance.list'))
            ->assertOk();

        // 前月ボタンを押す
        $response = $this->actingAs($this->user)
            ->get(route('attendance.list', [
                'month' => $previousMonth->format('Y-m')
            ]));

        $response->assertOk();

        // 前月が表示されている事を確認
        $response->assertSeeText($previousMonth->format('Y/m'));

        // 前月のデータのみ表示されている事を確認
        $response->assertDontSeeText(
            Carbon::parse($currentAttendance->work_date)->isoFormat('MM/DD(ddd)')
        );

        $response->assertSeeText(
            Carbon::parse($previousAttendance->work_date)->isoFormat('MM/DD(ddd)')
        );

        $response->assertDontSeeText(
            Carbon::parse($nextAttendance->work_date)->isoFormat('MM/DD(ddd)')
        );
    }

    /**
     * 項目: 勤怠一覧情報取得機能 (一般ユーザー)
     * 内容: 「翌月」を押下した時に表示月の前月の情報が表示される
     */
    public function test_next_month_is_displayed(): void
    {
        // 前月・当月・翌月の日付を準備
        $currentMonth = now()->startOfMonth();
        $previousMonth = $currentMonth->copy()->subMonthNoOverflow();
        $nextMonth = $currentMonth->copy()->addMonthNoOverflow();

        // 前月・当月・翌月の勤怠を1件ずつ作成
        $currentAttendance = Attendance::factory()
            ->for($this->user)
            ->forWorkDate($currentMonth)
            ->create();

        $previousAttendance = Attendance::factory()
            ->for($this->user)
            ->forWorkDate($previousMonth)
            ->create();

        $nextAttendance = Attendance::factory()
            ->for($this->user)
            ->forWorkDate($nextMonth)
            ->create();

        // ログインして勤怠一覧ページを開く
        $this->actingAs($this->user)
            ->get(route('attendance.list'))
            ->assertOk();

        // 翌月ボタンを押す
        $response = $this->actingAs($this->user)
            ->get(route('attendance.list', [
                'month' => $nextMonth->format('Y-m')
            ]));

        $response->assertOk();

        // 翌月が表示されている事を確認
        $response->assertSeeText($nextMonth->format('Y/m'));

        // 翌月のデータのみ表示されている事を確認
        $response->assertDontSeeText(
            Carbon::parse($currentAttendance->work_date)->isoFormat('MM/DD(ddd)')
        );

        $response->assertDontSeeText(
            Carbon::parse($previousAttendance->work_date)->isoFormat('MM/DD(ddd)')
        );

        $response->assertSeeText(
            Carbon::parse($nextAttendance->work_date)->isoFormat('MM/DD(ddd)')
        );
    }

    /**
     * 項目: 勤怠一覧情報取得機能 (一般ユーザー)
     * 内容: 「詳細」を押下すると、その日の勤怠詳細画面に遷移する
     */
    public function test_user_can_view_attendance_detail(): void
    {
        $currentMonth = now()->startOfMonth();

        // 勤怠を作成
        $attendance = Attendance::factory()
            ->for($this->user)
            ->forWorkDate($currentMonth)
            ->create();

        // ログインして勤怠一覧ページを開く
        $response = $this->actingAs($this->user)
            ->get(route('attendance.list'));

        $response->assertOk();

        // 勤怠一覧に詳細リンクがある事を確認
        $response->assertSee(
            route('attendance.detail', ['id' => $attendance->id]),
            false
        );

        // 詳細ボタンを押すと勤怠詳細画面に遷移する事を確認
        $detailResponse = $this->actingAs($this->user)
            ->get(route('attendance.detail', ['id' => $attendance->id]));

        $detailResponse->assertOk();
        $detailResponse->assertSeeText($attendance->work_date->format('Y') . '年');
        $detailResponse->assertSeeText($attendance->work_date->format('n月j日'));
    }

    /**
     * 項目: 勤怠一覧情報取得機能 (一般ユーザー)
     * 内容: (オプション) 月末日でも指定月の情報が正しく表示される
     */
    public function test_requested_month_is_displayed_correctly_even_when_current_day_is_month_end(): void
    {
        // 現在日付を月末に設定
        Carbon::setTestNow('2026-03-30 00:00:00');

        // 勤怠を作成
        $requestedMonth = Carbon::parse('2026-02-01')->startOfMonth();
        $otherMonth = Carbon::parse('2026-03-01')->startOfMonth();

        $requestedMonthAttendance = Attendance::factory()
            ->for($this->user)
            ->forWorkDate($requestedMonth)
            ->create();

        $otherMonthAttendance = Attendance::factory()
            ->for($this->user)
            ->forWorkDate($otherMonth)
            ->create();

        // 月末日の当月一覧を開く
        $this->actingAs($this->user)
            ->get(route('attendance.list'))
            ->assertOk();

        // 前月ボタン相当の月指定で一覧を開く
        $response = $this->actingAs($this->user)
            ->get(route('attendance.list', [
                'month' => $requestedMonth->format('Y-m'),
            ]));

        // 指定した月のデータのみ表示される事を確認
        $response->assertOk();
        $response->assertSeeText($requestedMonth->format('Y/m'));
        $response->assertSeeText(
            Carbon::parse($requestedMonthAttendance->work_date)->isoFormat('MM/DD(ddd)')
        );
        $response->assertDontSeeText(
            Carbon::parse($otherMonthAttendance->work_date)->isoFormat('MM/DD(ddd)')
        );
    }
}
