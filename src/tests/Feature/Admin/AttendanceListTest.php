<?php

namespace Tests\Feature\Admin;

use App\Models\Attendance;
use App\Models\AttendanceBreak;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AttendanceListTest extends TestCase
{
    use RefreshDatabase;

    protected Collection $users;
    protected User $admin;

    protected function setUp(): void
    {
        parent::setUp();

        Carbon::setTestNow('2026-04-15 00:00:00');

        $this->users = User::factory()->count(2)->create();
        $this->admin = User::factory()
            ->create(['role' => 'admin']);
    }

    protected function tearDown(): void
    {
        Carbon::setTestNow();
        parent::tearDown();
    }

    /**
     * 項目: 勤怠一覧情報取得機能 (管理者)
     * 内容: その日になされた全ユーザーの勤怠情報が正確に確認できる
     */
    public function test_admin_can_view_all_users_attendance_for_the_day(): void
    {
        $now = now();

        // 勤怠を作成
        $attendances = $this->users->map(function ($user) use ($now) {
            return Attendance::factory()
                ->for($user)
                ->forWorkDate($now)
                ->create();
        });

        // 休憩の作成
        foreach ($attendances as $attendance) {
            AttendanceBreak::factory()
                ->for($attendance)
                ->create();
        }

        // ログインして勤怠一覧画面を開く
        $response = $this->actingAs($this->admin)
            ->get(route('admin.attendance.list'));

        $response->assertOk();

        // 全ユーザーの勤怠が表示される事を確認
        foreach ($attendances as $attendance) {
            $response->assertSeeInOrder([
                $attendance->user->name,
                $attendance->clock_in->format('H:i'),
                $attendance->clock_out->format('H:i'),
                $attendance->breakTotalFormatted,
                $attendance->workTotalFormatted,
            ], false);
        }
    }

    /**
     * 項目: 勤怠一覧情報取得機能 (管理者)
     * 内容: 遷移した際に現在の日付が表示される
     */
    public function test_current_date_is_displayed(): void
    {
        $now = now();
        $previousDay = $now->copy()->subDay();
        $nextDay = $now->copy()->addDay();

        // 勤怠を作成
        $currentAttendances = $this->users->map(function ($user) use ($now) {
            return Attendance::factory()
                ->for($user)
                ->forWorkDate($now)
                ->create();
        });

        $previousAttendances = $this->users->map(function ($user) use ($previousDay) {
            return Attendance::factory()
                ->for($user)
                ->forWorkDate($previousDay)
                ->create();
        });

        $nextAttendances = $this->users->map(function ($user) use ($nextDay) {
            return Attendance::factory()
                ->for($user)
                ->forWorkDate($nextDay)
                ->create();
        });

        // ログインして勤怠一覧画面を開く
        $response = $this->actingAs($this->admin)
            ->get(route('admin.attendance.list'));

        $response->assertOk();

        // その日の日付が表示されている事を確認
        $response->assertSeeText($now->format('Y/m/d'));

        // その日の勤怠のみ表示されている事を確認
        foreach ($currentAttendances as $attendance) {
            $response->assertSee(
                'data-testid="attendance-row-' . $attendance->id . '"',
                false
            );
        }

        foreach ($previousAttendances as $attendance) {
            $response->assertDontSee(
                'data-testid="attendance-row-' . $attendance->id . '"',
                false
            );
        }

        foreach ($nextAttendances as $attendance) {
            $response->assertDontSee(
                'data-testid="attendance-row-' . $attendance->id . '"',
                false
            );
        }
    }

    /**
     * 項目: 勤怠一覧情報取得機能 (管理者)
     * 内容: 「前日」を押下した時に前の日の勤怠情報が表示される
     */
    public function test_admin_can_view_previous_day_attendance(): void
    {
        $now = now();
        $previousDay = $now->copy()->subDay();
        $nextDay = $now->copy()->addDay();

        // 勤怠を作成
        $currentAttendances = $this->users->map(function ($user) use ($now) {
            return Attendance::factory()
                ->for($user)
                ->forWorkDate($now)
                ->create();
        });

        $previousAttendances = $this->users->map(function ($user) use ($previousDay) {
            return Attendance::factory()
                ->for($user)
                ->forWorkDate($previousDay)
                ->create();
        });

        $nextAttendances = $this->users->map(function ($user) use ($nextDay) {
            return Attendance::factory()
                ->for($user)
                ->forWorkDate($nextDay)
                ->create();
        });

        // ログインして勤怠一覧画面を開く
        $this->actingAs($this->admin)
            ->get(route('admin.attendance.list'))
            ->assertOk();

        // 前日ボタンを押す
        $response = $this->actingAs($this->admin)
            ->get(route('admin.attendance.list', [
                'date' => $previousDay->format('Y-m-d')
            ]));

        $response->assertOk();

        // 前日が表示されている事を確認
        $response->assertSeeText($previousDay->format('Y/m/d'));

        // 前日の勤怠のみ表示されている事を確認
        foreach ($currentAttendances as $attendance) {
            $response->assertDontSee(
                'data-testid="attendance-row-' . $attendance->id . '"',
                false
            );
        }

        foreach ($previousAttendances as $attendance) {
            $response->assertSee(
                'data-testid="attendance-row-' . $attendance->id . '"',
                false
            );
        }

        foreach ($nextAttendances as $attendance) {
            $response->assertDontSee(
                'data-testid="attendance-row-' . $attendance->id . '"',
                false
            );
        }
    }

    /**
     * 項目: 勤怠一覧情報取得機能 (管理者)
     * 内容: 「翌日」を押下した時に次の日の勤怠情報が表示される
     */
    public function test_admin_can_view_next_day_attendance(): void
    {
        $now = now();
        $previousDay = $now->copy()->subDay();
        $nextDay = $now->copy()->addDay();

        // 勤怠を作成
        $currentAttendances = $this->users->map(function ($user) use ($now) {
            return Attendance::factory()
                ->for($user)
                ->forWorkDate($now)
                ->create();
        });

        $previousAttendances = $this->users->map(function ($user) use ($previousDay) {
            return Attendance::factory()
                ->for($user)
                ->forWorkDate($previousDay)
                ->create();
        });

        $nextAttendances = $this->users->map(function ($user) use ($nextDay) {
            return Attendance::factory()
                ->for($user)
                ->forWorkDate($nextDay)
                ->create();
        });

        // ログインして勤怠一覧画面を開く
        $this->actingAs($this->admin)
            ->get(route('admin.attendance.list'))
            ->assertOk();

        // 翌日ボタンを押す
        $response = $this->actingAs($this->admin)
            ->get(route('admin.attendance.list', [
                'date' => $nextDay->format('Y-m-d')
            ]));

        $response->assertOk();

        // 翌日が表示されている事を確認
        $response->assertSeeText($nextDay->format('Y/m/d'));

        // 翌日の勤怠のみ表示されている事を確認
        foreach ($currentAttendances as $attendance) {
            $response->assertDontSee(
                'data-testid="attendance-row-' . $attendance->id . '"',
                false
            );
        }

        foreach ($previousAttendances as $attendance) {
            $response->assertDontSee(
                'data-testid="attendance-row-' . $attendance->id . '"',
                false
            );
        }

        foreach ($nextAttendances as $attendance) {
            $response->assertSee(
                'data-testid="attendance-row-' . $attendance->id . '"',
                false
            );
        }
    }
}
