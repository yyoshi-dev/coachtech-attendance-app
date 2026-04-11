<?php

namespace Tests\Feature\User;

use App\Models\Attendance;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ClockOutTest extends TestCase
{
    use RefreshDatabase;

    protected User $user;
    protected string $workDate;

    protected function setUp(): void
    {
        parent::setUp();

        Carbon::setTestNow('2026-04-10 00:00:00');

        $this->user = User::factory()->create();
        $this->workDate = now()->toDateString();
    }

    protected function tearDown(): void
    {
        Carbon::setTestNow();
        parent::tearDown();
    }

    /**
     * 項目: 退勤機能
     * 内容: 退勤ボタンが正しく機能する
     */
    public function test_user_can_clock_out(): void
    {
        // 出勤時間を定義
        $clockIn  = Carbon::parse($this->workDate)->setTime(9, 0, 0);

        // 出勤中の勤怠を作成
        $attendance = Attendance::factory()
            ->for($this->user)
            ->forWorkDate($this->workDate)
            ->create([
                'clock_in' => $clockIn,
                'clock_out' => null,
            ]);

        // ログインして勤怠打刻画面を開く
        $response = $this->actingAs($this->user)
            ->get(route('attendance.index'));

        $response->assertOk();

        // 画面に表示されているステータスが出勤中である事を確認
        $response->assertSeeText('出勤中');

        // 画面上に退勤ボタンが表示されている事を確認
        $response->assertSee('data-testid="clock-out-button"', false);
        $response->assertSeeText('退勤');

        // 退勤時間を固定
        Carbon::setTestNow('2026-04-10 18:00:00');
        $clockOut = now()->toDateTimeString();

        // 退勤処理を行う
        $this->actingAs($this->user)
            ->post(route('attendance.clock-out'))
            ->assertRedirect(route('attendance.index'));

        // 退勤がレコードに追加された事を確認
        $attendance->refresh();
        $this->assertDatabaseCount('attendances', 1);
        $this->assertSame($clockOut, $attendance->clock_out->format('Y-m-d H:i:s'));

        // 画面に表示されているステータスが退勤済に変わる事を確認
        $this->actingAs($this->user)
            ->get(route('attendance.index'))
            ->assertSeeText('退勤済');
    }

    /**
     * 項目: 退勤機能
     * 内容: 退勤時刻が勤怠一覧画面で確認できる
     */
    public function test_attendance_list_shows_clock_out_time(): void
    {
        // 勤怠レコードがない事を確認
        $this->assertDatabaseCount('attendances', 0);

        // ログインして勤怠打刻画面を開く
        $this->actingAs($this->user)
            ->get(route('attendance.index'))
            ->assertOk();

        // 出勤時間を固定
        Carbon::setTestNow('2026-04-10 09:00:00');
        $clockIn = now()->toDateTimeString();

        // 出勤処理を行う
        $this->actingAs($this->user)
            ->post(route('attendance.clock-in'))
            ->assertRedirect(route('attendance.index'));

        // 退勤時間を固定
        Carbon::setTestNow('2026-04-10 18:00:00');
        $clockOut = now()->toDateTimeString();

        // 退勤処理を行う
        $this->actingAs($this->user)
            ->post(route('attendance.clock-out'))
            ->assertRedirect(route('attendance.index'));

        // 勤怠が追加された事を確認
        $this->assertDatabaseCount('attendances', 1);

        $attendance = Attendance::where('user_id', $this->user->id)
            ->whereDate('work_date', $this->workDate)
            ->firstOrFail();

        $this->assertSame($this->workDate, $attendance->work_date->format('Y-m-d'));
        $this->assertSame($clockIn, $attendance->clock_in->format('Y-m-d H:i:s'));
        $this->assertSame($clockOut, $attendance->clock_out->format('Y-m-d H:i:s'));

        // 勤怠一覧を開く
        $response = $this->actingAs($this->user)
            ->get(route('attendance.list'));

        $response->assertOk();

        // 退勤時刻を確認
        $response->assertSeeInOrder([
            Carbon::parse($this->workDate)->isoFormat('MM/DD(ddd)'),
            Carbon::parse($clockIn)->format('H:i'),
            Carbon::parse($clockOut)->format('H:i'),
        ]);
    }

    /**
     * 項目: 退勤機能
     * 内容: (オプション) 退勤済状態で再度退勤できない
     */
    public function test_finished_user_cannot_clock_out_again(): void
    {
        // 出退勤時間を定義
        $clockIn  = Carbon::parse($this->workDate)->setTime(9, 0, 0);
        $clockOut  = Carbon::parse($this->workDate)->setTime(18, 0, 0);

        // 退勤済の勤怠を作成
        Attendance::factory()
            ->for($this->user)
            ->forWorkDate($this->workDate)
            ->create([
                'clock_in' => $clockIn,
                'clock_out' => $clockOut,
            ]);

        // 勤怠レコードが1件であり、登録内容と一致する事を確認
        $this->assertDatabaseCount('attendances', 1);

        $attendance = Attendance::where('user_id', $this->user->id)
            ->whereDate('work_date', $this->workDate)
            ->firstOrFail();

        $this->assertSame($this->workDate, $attendance->work_date->format('Y-m-d'));
        $this->assertSame($clockIn->format('Y-m-d H:i:s'), $attendance->clock_in->format('Y-m-d H:i:s'));
        $this->assertSame($clockOut->format('Y-m-d H:i:s'), $attendance->clock_out->format('Y-m-d H:i:s'));

        // ログインして勤怠打刻画面を開く
        $this->actingAs($this->user)
            ->get(route('attendance.index'))
            ->assertOk();

        // 退勤時間を固定
        Carbon::setTestNow('2026-04-10 20:00:00');

        // 退勤処理を行う
        $this->actingAs($this->user)
            ->post(route('attendance.clock-out'))
            ->assertRedirect(route('attendance.index'));

        // 勤怠レコードが1件のままであり、内容も更新されていない事を確認
        $attendance->refresh();

        $this->assertDatabaseCount('attendances', 1);

        $this->assertSame($this->workDate, $attendance->work_date->format('Y-m-d'));
        $this->assertSame($clockIn->format('Y-m-d H:i:s'), $attendance->clock_in->format('Y-m-d H:i:s'));
        $this->assertSame($clockOut->format('Y-m-d H:i:s'), $attendance->clock_out->format('Y-m-d H:i:s'));
    }

    /**
     * 項目: 退勤機能
     * 内容: (オプション) 出勤していない状態で退勤できない
     */
    public function test_user_cannot_clock_out_without_clock_in(): void
    {
        // 勤怠レコードがない事を確認
        $this->assertDatabaseCount('attendances', 0);

        // ログインして勤怠打刻画面を開く
        $this->actingAs($this->user)
            ->get(route('attendance.index'))
            ->assertOk();

        // 退勤時間を固定
        Carbon::setTestNow('2026-04-10 18:00:00');

        // 退勤処理を行う
        $this->actingAs($this->user)
            ->post(route('attendance.clock-out'))
            ->assertRedirect(route('attendance.index'));

        // 勤怠レコードが追加されていない事を確認
        $this->assertDatabaseCount('attendances', 0);
    }
}
