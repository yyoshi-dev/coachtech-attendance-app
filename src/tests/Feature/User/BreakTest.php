<?php

namespace Tests\Feature\User;

use App\Models\Attendance;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class BreakTest extends TestCase
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
     * 項目: 休憩機能
     * 内容: 休憩ボタンが正しく機能する
     */
    public function test_user_can_break_start(): void
    {
        // 出勤時間を定義
        $clockIn  = Carbon::parse($this->workDate)->setTime(9, 0, 0);

        // 休憩入時間を固定
        Carbon::setTestNow('2026-04-10 12:00:00');
        $breakStart = now()->toDateTimeString();

        // 出勤中の勤怠を作成
        $attendance = Attendance::factory()
            ->for($this->user)
            ->forWorkDate($this->workDate)
            ->create([
                'clock_in' => $clockIn,
                'clock_out' => null,
            ]);

        // 休憩レコードがない事を確認
        $this->assertDatabaseCount('attendance_breaks', 0);

        // ログインして勤怠打刻画面を開く
        $response = $this->actingAs($this->user)
            ->get(route('attendance.index'));

        $response->assertOk();

        // 画面に表示されているステータスが出勤中である事を確認
        $response->assertSeeText('出勤中');

        // 画面上に休憩入ボタンが表示されている事を確認
        $response->assertSee('data-testid="break-start-button"', false);
        $response->assertSeeText('休憩入');

        // 休憩入処理を行う
        $this->actingAs($this->user)
            ->post(route('attendance.break-start'))
            ->assertRedirect(route('attendance.index'));

        // 休憩レコードが追加された事を確認
        $this->assertDatabaseCount('attendance_breaks', 1);

        $this->assertDatabaseHas('attendance_breaks', [
            'attendance_id' => $attendance->id,
            'break_start' => $breakStart,
            'break_end' => null,
        ]);

        // 画面に表示されているステータスが休憩中に変わる事を確認
        $this->actingAs($this->user)
            ->get(route('attendance.index'))
            ->assertSeeText('休憩中');
    }

    /**
     * 項目: 休憩機能
     * 内容: 休憩は一日に何回でもできる
     */
    public function test_break_start_is_allowed_multiple_times(): void
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

        // 休憩レコードがない事を確認
        $this->assertDatabaseCount('attendance_breaks', 0);

        // ログインして勤怠打刻画面を開く
        $this->actingAs($this->user)
            ->get(route('attendance.index'))
            ->assertOk();

        // 休憩入時間を固定
        Carbon::setTestNow('2026-04-10 12:00:00');
        $breakStart = now()->toDateTimeString();

        // 休憩入処理を行う
        $this->actingAs($this->user)
            ->post(route('attendance.break-start'))
            ->assertRedirect(route('attendance.index'));

        // 休憩戻時間を固定
        Carbon::setTestNow('2026-04-10 13:00:00');
        $breakEnd = now()->toDateTimeString();

        // 休憩戻処理を行う
        $this->actingAs($this->user)
            ->post(route('attendance.break-end'))
            ->assertRedirect(route('attendance.index'));

        // 休憩レコードが追加された事を確認
        $this->assertDatabaseCount('attendance_breaks', 1);

        $this->assertDatabaseHas('attendance_breaks', [
            'attendance_id' => $attendance->id,
            'break_start' => $breakStart,
            'break_end' => $breakEnd,
        ]);

        // 画面に表示されているステータスが出勤中に変わる事を確認
        $response = $this->actingAs($this->user)
            ->get(route('attendance.index'));

        $response->assertOk();
        $response->assertSeeText('出勤中');

        // 画面上に休憩入ボタンが表示されている事を確認
        $response->assertSee('data-testid="break-start-button"', false);
        $response->assertSeeText('休憩入');

        // 2回目の休憩入時間を固定
        Carbon::setTestNow('2026-04-10 15:00:00');
        $secondBreakStart = now()->toDateTimeString();

        // 2回目の休憩入処理を行う
        $this->actingAs($this->user)
            ->post(route('attendance.break-start'))
            ->assertRedirect(route('attendance.index'));

        // 休憩レコードが2件になっている事を確認
        $this->assertDatabaseCount('attendance_breaks', 2);

        $this->assertDatabaseHas('attendance_breaks', [
            'attendance_id' => $attendance->id,
            'break_start' => $secondBreakStart,
            'break_end' => null,
        ]);
    }

    /**
     * 項目: 休憩機能
     * 内容: 休憩戻ボタンが正しく機能する
     */
    public function test_user_can_break_end(): void
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

        // 休憩レコードがない事を確認
        $this->assertDatabaseCount('attendance_breaks', 0);

        // ログインして勤怠打刻画面を開く
        $this->actingAs($this->user)
            ->get(route('attendance.index'))
            ->assertOk();

        // 休憩入時間を固定
        Carbon::setTestNow('2026-04-10 12:00:00');
        $breakStart = now()->toDateTimeString();

        // 休憩入処理を行う
        $this->actingAs($this->user)
            ->post(route('attendance.break-start'))
            ->assertRedirect(route('attendance.index'));

        // 画面に表示されているステータスが休憩中である事を確認
        $response = $this->actingAs($this->user)
            ->get(route('attendance.index'));

        $response->assertOk();
        $response->assertSeeText('休憩中');

        // 画面上に休憩戻ボタンが表示されている事を確認
        $response->assertSee('data-testid="break-end-button"', false);
        $response->assertSeeText('休憩戻');

        // 休憩戻時間を固定
        Carbon::setTestNow('2026-04-10 13:00:00');
        $breakEnd = now()->toDateTimeString();

        // 休憩戻処理を行う
        $this->actingAs($this->user)
            ->post(route('attendance.break-end'))
            ->assertRedirect(route('attendance.index'));

        // 休憩レコードが追加された事を確認
        $this->assertDatabaseCount('attendance_breaks', 1);

        $this->assertDatabaseHas('attendance_breaks', [
            'attendance_id' => $attendance->id,
            'break_start' => $breakStart,
            'break_end' => $breakEnd,
        ]);

        // 画面に表示されているステータスが出勤中に変わる事を確認
        $breakEndResponse = $this->actingAs($this->user)
            ->get(route('attendance.index'));

        $breakEndResponse->assertOk();
        $breakEndResponse->assertSeeText('出勤中');

        // 画面上に休憩入ボタンが表示されている事を確認
        $breakEndResponse->assertSee('data-testid="break-start-button"', false);
        $breakEndResponse->assertSeeText('休憩入');
    }

    /**
     * 項目: 休憩機能
     * 内容: 休憩戻は一日に何回でもできる
     */
    public function test_break_end_is_allowed_multiple_times(): void
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

        // 休憩レコードがない事を確認
        $this->assertDatabaseCount('attendance_breaks', 0);

        // ログインして勤怠打刻画面を開く
        $this->actingAs($this->user)
            ->get(route('attendance.index'))
            ->assertOk();

        // 休憩入時間を固定
        Carbon::setTestNow('2026-04-10 12:00:00');
        $breakStart = now()->toDateTimeString();

        // 休憩入処理を行う
        $this->actingAs($this->user)
            ->post(route('attendance.break-start'))
            ->assertRedirect(route('attendance.index'));

        // 休憩戻時間を固定
        Carbon::setTestNow('2026-04-10 13:00:00');
        $breakEnd = now()->toDateTimeString();

        // 休憩戻処理を行う
        $this->actingAs($this->user)
            ->post(route('attendance.break-end'))
            ->assertRedirect(route('attendance.index'));

        // 休憩レコードが追加された事を確認
        $this->assertDatabaseCount('attendance_breaks', 1);

        $this->assertDatabaseHas('attendance_breaks', [
            'attendance_id' => $attendance->id,
            'break_start' => $breakStart,
            'break_end' => $breakEnd,
        ]);

        // 2回目の休憩入時間を固定
        Carbon::setTestNow('2026-04-10 15:00:00');
        $secondBreakStart = now()->toDateTimeString();

        // 2回目の休憩入処理を行う
        $this->actingAs($this->user)
            ->post(route('attendance.break-start'))
            ->assertRedirect(route('attendance.index'));

        // 画面に表示されているステータスが休憩中である事を確認
        $response = $this->actingAs($this->user)
            ->get(route('attendance.index'));

        $response->assertOk();
        $response->assertSeeText('休憩中');

        // 画面上に休憩戻ボタンが表示されている事を確認
        $response->assertSee('data-testid="break-end-button"', false);
        $response->assertSeeText('休憩戻');

        // 2回目の休憩戻時間を固定
        Carbon::setTestNow('2026-04-10 15:15:00');
        $secondBreakEnd = now()->toDateTimeString();

        // 2回目の休憩戻処理を行う
        $this->actingAs($this->user)
            ->post(route('attendance.break-end'))
            ->assertRedirect(route('attendance.index'));

        // 休憩レコードが2件になっている事を確認
        $this->assertDatabaseCount('attendance_breaks', 2);

        $this->assertDatabaseHas('attendance_breaks', [
            'attendance_id' => $attendance->id,
            'break_start' => $secondBreakStart,
            'break_end' => $secondBreakEnd,
        ]);
    }

    /**
     * 項目: 休憩機能
     * 内容: 休憩時刻が勤怠一覧画面で確認できる
     */
    public function test_attendance_list_shows_break_times(): void
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

        // 休憩レコードがない事を確認
        $this->assertDatabaseCount('attendance_breaks', 0);

        // ログインして勤怠打刻画面を開く
        $this->actingAs($this->user)
            ->get(route('attendance.index'))
            ->assertOk();

        // 休憩入時間を固定
        Carbon::setTestNow('2026-04-10 12:00:00');
        $breakStart = now()->toDateTimeString();

        // 休憩入処理を行う
        $this->actingAs($this->user)
            ->post(route('attendance.break-start'))
            ->assertRedirect(route('attendance.index'));

        // 休憩戻時間を固定
        Carbon::setTestNow('2026-04-10 13:00:00');
        $breakEnd = now()->toDateTimeString();

        // 休憩戻処理を行う
        $this->actingAs($this->user)
            ->post(route('attendance.break-end'))
            ->assertRedirect(route('attendance.index'));

        // 2回目の休憩入時間を固定
        Carbon::setTestNow('2026-04-10 15:00:00');
        $secondBreakStart = now()->toDateTimeString();

        // 2回目の休憩入処理を行う
        $this->actingAs($this->user)
            ->post(route('attendance.break-start'))
            ->assertRedirect(route('attendance.index'));

        // 2回目の休憩戻時間を固定
        Carbon::setTestNow('2026-04-10 15:15:00');
        $secondBreakEnd = now()->toDateTimeString();

        // 2回目の休憩戻処理を行う
        $this->actingAs($this->user)
            ->post(route('attendance.break-end'))
            ->assertRedirect(route('attendance.index'));

        // 勤怠一覧画面を開く
        $response = $this->actingAs($this->user)
            ->get(route('attendance.list'));

        $response->assertOk();

        // 勤怠が1件だけである事を確認
        $this->assertDatabaseCount('attendances', 1);

        // 休憩レコードが2件であり、登録内容と一致する事を確認
        $this->assertDatabaseCount('attendance_breaks', 2);

        $this->assertDatabaseHas('attendance_breaks', [
            'attendance_id' => $attendance->id,
            'break_start' => $breakStart,
            'break_end' => $breakEnd,
        ]);

        $this->assertDatabaseHas('attendance_breaks', [
            'attendance_id' => $attendance->id,
            'break_start' => $secondBreakStart,
            'break_end' => $secondBreakEnd,
        ]);

        // 休憩時間を計算
        $attendance->refresh();

        $breakTotalSeconds =
            Carbon::parse($breakStart)->diffInSeconds(Carbon::parse($breakEnd)) +
            Carbon::parse($secondBreakStart)->diffInSeconds(Carbon::parse($secondBreakEnd));

        $breakTotalHours = intdiv($breakTotalSeconds, 3600);
        $breakTotalMinutes = intdiv($breakTotalSeconds % 3600, 60);
        $breakTotal = sprintf('%d:%02d', $breakTotalHours, $breakTotalMinutes);

        // モデルの結果と一致するかを確認
        $this->assertSame($breakTotal, $attendance->breakTotalFormatted);

        // 勤怠一覧画面に休憩時刻 (合計時間)が表示される事を確認
        $response->assertSeeInOrder([
            Carbon::parse($this->workDate)->isoFormat('MM/DD(ddd)'),
            $breakTotal,
        ]);
    }

    /**
     * 項目: 休憩機能
     * 内容: (オプション) 休憩中は再度休憩開始できない
     */
    public function test_user_cannot_break_start_while_on_break(): void
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

        // 休憩レコードがない事を確認
        $this->assertDatabaseCount('attendance_breaks', 0);

        // ログインして勤怠打刻画面を開く
        $this->actingAs($this->user)
            ->get(route('attendance.index'))
            ->assertOk();

        // 休憩入時間を固定
        Carbon::setTestNow('2026-04-10 12:00:00');
        $breakStart = now()->toDateTimeString();

        // 休憩入処理を行う
        $this->actingAs($this->user)
            ->post(route('attendance.break-start'))
            ->assertRedirect(route('attendance.index'));

        // 休憩レコードが1件であり、登録内容と一致する事を確認
        $this->assertDatabaseCount('attendance_breaks', 1);

        $this->assertDatabaseHas('attendance_breaks', [
            'attendance_id' => $attendance->id,
            'break_start' => $breakStart,
            'break_end' => null,
        ]);

        // 2回目の休憩入時間を固定
        Carbon::setTestNow('2026-04-10 15:00:00');

        // 2回目の休憩入処理を行う
        $this->actingAs($this->user)
            ->post(route('attendance.break-start'))
            ->assertRedirect(route('attendance.index'));

        // 休憩レコードが1件のままであり、内容も更新されていない事を確認
        $this->assertDatabaseCount('attendance_breaks', 1);

        $this->assertDatabaseHas('attendance_breaks', [
            'attendance_id' => $attendance->id,
            'break_start' => $breakStart,
            'break_end' => null,
        ]);
    }

    /**
     * 項目: 休憩機能
     * 内容: (オプション) 休憩していない状態では休憩終了できない
     */
    public function test_user_cannot_break_end_without_break_start(): void
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

        // 休憩レコードがない事を確認
        $this->assertDatabaseCount('attendance_breaks', 0);

        // ログインして勤怠打刻画面を開く
        $this->actingAs($this->user)
            ->get(route('attendance.index'))
            ->assertOk();

        // 休憩戻時間を固定
        Carbon::setTestNow('2026-04-10 13:00:00');

        // 休憩戻処理を行う
        $this->actingAs($this->user)
            ->post(route('attendance.break-end'))
            ->assertRedirect(route('attendance.index'));

        // 休憩レコードが追加されていない事を確認
        $this->assertDatabaseCount('attendance_breaks', 0);
    }
}
