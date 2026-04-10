<?php

namespace Tests\Feature\User;

use App\Models\Attendance;
use App\Models\AttendanceBreak;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AttendanceStatusTest extends TestCase
{
    use RefreshDatabase;

    protected User $user;
    protected string $workDate;

    protected function setUp(): void
    {
        parent::setUp();

        Carbon::setTestNow('2026-04-10 09:00:00');

        $this->user = User::factory()->create();
        $this->workDate = now()->toDateString();
    }

    protected function tearDown(): void
    {
        Carbon::setTestNow();
        parent::tearDown();
    }

    /**
     * 項目: ステータス確認機能
     * 内容: 勤務外の場合、勤怠ステータスが正しく表示される
     */
    public function test_attendance_status_is_displayed_as_off_duty(): void
    {
        // ログインして勤怠打刻画面を開く
        $response = $this->actingAs($this->user)
            ->get(route('attendance.index'));

        $response->assertOk();

        // 画面に表示されているステータスを確認
        $response->assertSeeText('勤務外');
    }

    /**
     * 項目: ステータス確認機能
     * 内容: 出勤中の場合、勤怠ステータスが正しく表示される
     */
    public function test_attendance_status_is_displayed_as_working(): void
    {
        // 出勤中の勤怠を作成
        Attendance::factory()
            ->for($this->user)
            ->forWorkDate($this->workDate)
            ->state([
                'clock_out' => null,
            ])
            ->create();

        // ログインして勤怠打刻画面を開く
        $response = $this->actingAs($this->user)
            ->get(route('attendance.index'));

        $response->assertOk();

        // 画面に表示されているステータスを確認
        $response->assertSeeText('出勤中');
    }

    /**
     * 項目: ステータス確認機能
     * 内容: 休憩中の場合、勤怠ステータスが正しく表示される
     */
    public function test_attendance_status_is_displayed_as_on_break(): void
    {
        // 出勤中の勤怠を作成
        $attendance = Attendance::factory()
            ->for($this->user)
            ->forWorkDate($this->workDate)
            ->state([
                'clock_out' => null,
            ])
            ->create();

        // 休憩を作成
        AttendanceBreak::factory()
            ->for($attendance)
            ->state([
                'break_start' => $attendance->clock_in->copy()->addHour(),
                'break_end' => null,
            ])
            ->create();

        // ログインして勤怠打刻画面を開く
        $response = $this->actingAs($this->user)
            ->get(route('attendance.index'));

        $response->assertOk();

        // 画面に表示されているステータスを確認
        $response->assertSeeText('休憩中');
    }

    /**
     * 項目: ステータス確認機能
     * 内容: 退勤済の場合、勤怠ステータスが正しく表示される
     */
    public function test_attendance_status_is_displayed_as_finished_work(): void
    {
        // 退勤済の勤怠を作成
        Attendance::factory()
            ->for($this->user)
            ->forWorkDate($this->workDate)
            ->create();

        // ログインして勤怠打刻画面を開く
        $response = $this->actingAs($this->user)
            ->get(route('attendance.index'));

        $response->assertOk();

        // 画面に表示されているステータスを確認
        $response->assertSeeText('退勤済');
    }
}
