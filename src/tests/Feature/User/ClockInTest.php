<?php

namespace Tests\Feature\User;

use App\Models\Attendance;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ClockInTest extends TestCase
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
     * 項目: 出勤機能
     * 内容: 出勤ボタンが正しく機能する
     */
    public function test_user_can_clock_in(): void
    {
        // ログインして勤怠打刻画面を開く
        $response = $this->actingAs($this->user)
            ->get(route('attendance.index'));

        $response->assertOk();

        // 画面に表示されているステータスが勤務外である事を確認
        $response->assertSeeText('勤務外');

        // 画面上に出勤ボタンが表示されている事を確認
        $response->assertSee('data-testid="clock-in-button"', false);
        $response->assertSeeText('出勤');

        // 出勤時間を固定
        Carbon::setTestNow('2026-04-10 09:00:00');
        $clockIn = now()->toDateTimeString();

        // 出勤処理を行う
        $this->actingAs($this->user)
            ->post(route('attendance.clock-in'))
            ->assertRedirect(route('attendance.index'));

        // 出勤が登録された事を確認
        $attendance = Attendance::where('user_id', $this->user->id)
            ->whereDate('work_date', $this->workDate)
            ->firstOrFail();

        $this->assertSame($this->workDate, $attendance->work_date->format('Y-m-d'));
        $this->assertSame($clockIn, $attendance->clock_in->format('Y-m-d H:i:s'));
        $this->assertNull($attendance->clock_out);

        // 画面に表示されているステータスが出勤中に変わる事を確認
        $this->actingAs($this->user)
            ->get(route('attendance.index'))
            ->assertSeeText('出勤中');
    }

    /**
     * 項目: 出勤機能
     * 内容: 出勤は一日一回のみできる
     */
    public function test_finished_user_cannot_clock_in_again(): void
    {
        // 出退勤時間を定義
        $clockIn  = Carbon::parse($this->workDate)->setTime(9, 0, 0);
        $clockOut = Carbon::parse($this->workDate)->setTime(18, 0, 0);

        // 退勤済の勤怠を作成
        $attendance = Attendance::factory()
            ->for($this->user)
            ->forWorkDate($this->workDate)
            ->create([
                'clock_in' => $clockIn,
                'clock_out' => $clockOut,
            ]);

        // ログインして勤怠打刻画面を開く
        $response = $this->actingAs($this->user)
            ->get(route('attendance.index'));

        $response->assertOk();

        // 画面に表示されているステータスが退勤済である事を確認
        $response->assertSeeText('退勤済');

        // 画面上に出勤ボタンが表示されていない事を確認
        $response->assertDontSee('data-testid="clock-in-button"', false);
        $response->assertSee('お疲れ様でした。');

        // ボタン非表示だけでなく、直POSTでも再出勤出来ない事を確認
        // 現在時刻をsetupの時刻からずらす
        Carbon::setTestNow('2026-04-10 19:00:00');

        // 出勤を直で実行
        $this->actingAs($this->user)
            ->post(route('attendance.clock-in'))
            ->assertRedirect(route('attendance.index'));

        // レコードが追加されていない事の確認
        $this->assertSame(
            1,
            Attendance::where('user_id', $this->user->id)
                ->whereDate('work_date', $this->workDate)
                ->count()
        );

        // 既存レコードが変更されていない
        $attendance->refresh();
        $this->assertSame($clockIn->format('Y-m-d H:i:s'), $attendance->clock_in->format('Y-m-d H:i:s'));
        $this->assertSame($clockOut->format('Y-m-d H:i:s'), $attendance->clock_out->format('Y-m-d H:i:s'));
    }

    /**
     * 項目: 出勤機能
     * 内容: 出勤時刻が勤怠一覧画面で確認できる
     */
    public function test_attendance_list_shows_clock_in_time(): void
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

        // 勤怠が追加された事を確認
        $this->assertDatabaseCount('attendances', 1);

        // 出勤が登録された事を確認
        $attendance = Attendance::where('user_id', $this->user->id)
            ->whereDate('work_date', $this->workDate)
            ->firstOrFail();

        $this->assertSame($this->workDate, $attendance->work_date->format('Y-m-d'));
        $this->assertSame($clockIn, $attendance->clock_in->format('Y-m-d H:i:s'));
        $this->assertNull($attendance->clock_out);

        // 勤怠一覧を開く
        $response = $this->actingAs($this->user)
            ->get(route('attendance.list'));

        $response->assertOk();

        // 出勤時刻を確認
        $response->assertSeeInOrder([
            Carbon::parse($this->workDate)->isoFormat('MM/DD(ddd)'),
            Carbon::parse($clockIn)->format('H:i'),
        ]);
    }

    /**
     * 項目: 出勤機能
     * 内容: (オプション) 出勤中状態で再度出勤できない
     */
    public function test_user_cannot_clock_in_while_working(): void
    {
        // 出勤時間を定義
        $clockIn  = Carbon::parse($this->workDate)->setTime(9, 0, 0);

        // 出勤中の勤怠を作成
        Attendance::factory()
            ->for($this->user)
            ->forWorkDate($this->workDate)
            ->create([
                'clock_in' => $clockIn,
                'clock_out' => null,
            ]);

        // 勤怠レコードが1件である事を確認
        $this->assertDatabaseCount('attendances', 1);

        // ログインして勤怠打刻画面を開く
        $this->actingAs($this->user)
            ->get(route('attendance.index'))
            ->assertOk();

        // 出勤時間を固定
        Carbon::setTestNow('2026-04-10 13:00:00');

        // 出勤処理を行う
        $this->actingAs($this->user)
            ->post(route('attendance.clock-in'))
            ->assertRedirect(route('attendance.index'));

        // 勤怠レコードが1件のままであり、内容も更新されていない事を確認
        $this->assertDatabaseCount('attendances', 1);

        $attendance = Attendance::where('user_id', $this->user->id)
            ->whereDate('work_date', $this->workDate)
            ->firstOrFail();

        $this->assertSame($this->workDate, $attendance->work_date->format('Y-m-d'));
        $this->assertSame($clockIn->format('Y-m-d H:i:s'), $attendance->clock_in->format('Y-m-d H:i:s'));
        $this->assertNull($attendance->clock_out);
    }
}
