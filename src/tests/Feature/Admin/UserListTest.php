<?php

namespace Tests\Feature\Admin;

use App\Models\Attendance;
use App\Models\AttendanceBreak;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class UserListTest extends TestCase
{
    use RefreshDatabase;

    protected User $user;
    protected User $admin;

    protected function setUp(): void
    {
        parent::setUp();

        $this->user = User::factory()->create();
        $this->admin = User::factory()
            ->create(['role' => 'admin']);
    }

    /**
     * 項目: ユーザー情報取得機能 (管理者)
     * 内容: 管理者ユーザーが全一般ユーザーの「氏名」「メールアドレス」を確認できる
     */
    public function test_admin_can_view_all_users_data(): void
    {
        // ユーザーを作成
        $users = User::factory()->count(3)->create();
        $allUsers = $users->push($this->user);

        // 管理者でログインして、スタッフ一覧を開く
        $response = $this->actingAs($this->admin)
            ->get(route('admin.staff.list'));

        $response->assertOk();

        // 全ての一般ユーザーの氏名とメールアドレスが表示される事を確認
        foreach ($allUsers as $user) {
            $response->assertSeeInOrder([
                'data-testid="staff-row-' . $user->id . '"',
                $user->name,
                $user->email,
            ], false);
        }

        // 管理者は表示されない事を確認
        $response->assertDontSeeText($this->admin->email);
    }

    /**
     * 項目: ユーザー情報取得機能 (管理者)
     * 内容: ユーザーの勤怠情報が正しく表示される
     */
    public function test_admin_can_view_user_attendance_list(): void
    {
        $other = User::factory()
            ->create(['name' => 'Other User']);

        // 勤怠用の日付リストを作成
        $start = now()->startOfMonth();
        $end = $start->copy()->addDays(15);
        $dates = $start->daysUntil($end);

        // 勤怠を作成
        $attendances = collect();
        $otherAttendances = collect();

        foreach ($dates as $index => $date) {
            $attendance = Attendance::factory()
                ->for($this->user)
                ->forWorkDate($date)
                ->create();

            $otherAttendance = Attendance::factory()
                ->for($other)
                ->forWorkDate($date)
                ->create([
                    'clock_in' => $date->copy()->setTime(1, $index, 0),
                    'clock_out' => $date->copy()->setTime(23, $index, 0),
                ]);

            AttendanceBreak::factory()
                ->for($attendance)
                ->create();

            $attendance->refresh();
            $attendances->push($attendance);
            $otherAttendances->push($otherAttendance);
        }

        // 管理者でログインして、スタッフ一覧を開く
        $this->actingAs($this->admin)
            ->get(route('admin.staff.list'))
            ->assertOk();

        // 選択したユーザーの勤怠一覧を開く
        $response = $this->actingAs($this->admin)
            ->get(route('admin.attendance.staff.monthly', [
                'id' => $this->user->id
            ]));

        $response->assertOk();

        // 選択したユーザーの名前が表示される事を確認
        $response->assertSeeText($this->user->name);
        $response->assertDontSeeText($other->name);

        // 選択したユーザーの勤怠情報が表示される事を確認
        foreach ($attendances as $attendance) {
            $response->assertSeeInOrder([
                $attendance->work_date->isoFormat('MM/DD(ddd)'),
                $attendance->clock_in->format('H:i'),
                $attendance->clock_out->format('H:i'),
                $attendance->breakTotalFormatted,
                $attendance->workTotalFormatted,
            ], false);
        }

        // 選択していないユーザーの勤怠情報が表示されない事を確認
        foreach ($otherAttendances as $attendance) {
            $response->assertDontSeeText(
                $attendance->clock_in->format('H:i')
            );
            $response->assertDontSeeText(
                $attendance->clock_out->format('H:i')
            );
        }
    }

    /**
     * 項目: ユーザー情報取得機能 (管理者)
     * 内容: 「前月」を押下した時に表示月の前月の情報が表示される
     */
    public function test_admin_can_view_previous_month_attendance(): void
    {
        // 前月・当月・翌月の日付を準備
        $currentMonth = now()->startOfMonth();
        $previousMonth = $currentMonth->copy()->subMonthNoOverflow();
        $nextMonth = $currentMonth->copy()->addMonthNoOverflow();

        // 勤怠を作成
        $currentAttendance = Attendance::factory()
            ->for($this->user)
            ->forWorkDate($currentMonth)
            ->create([
                'clock_in' => $currentMonth->copy()->setTime(1, 0, 0),
                'clock_out' => $currentMonth->copy()->setTime(2, 0, 0),
            ]);

        $previousAttendance = Attendance::factory()
            ->for($this->user)
            ->forWorkDate($previousMonth)
            ->create([
                'clock_in' => $previousMonth->copy()->setTime(3, 0, 0),
                'clock_out' => $previousMonth->copy()->setTime(4, 0, 0),
            ]);

        $nextAttendance = Attendance::factory()
            ->for($this->user)
            ->forWorkDate($nextMonth)
            ->create([
                'clock_in' => $nextMonth->copy()->setTime(5, 0, 0),
                'clock_out' => $nextMonth->copy()->setTime(6, 0, 0),
            ]);

        // 管理者でログインして、勤怠一覧を開く
        $this->actingAs($this->admin)
            ->get(route('admin.attendance.staff.monthly', [
                'id' => $this->user->id
            ]))
            ->assertOk();

        // 前月ボタンを押す
        $response = $this->actingAs($this->admin)
            ->get(route('admin.attendance.staff.monthly', [
                'id' => $this->user->id,
                'month' => $previousMonth->format('Y-m'),
            ]));

        $response->assertOk();

        // 前月が表示されている事を確認
        $response->assertSeeText($previousMonth->format('Y/m'));

        // 前月のデータのみ表示されている事を確認
        $response->assertDontSeeText(
            $currentAttendance->work_date->isoFormat('MM/DD(ddd)')
        );
        $response->assertDontSeeText(
            $currentAttendance->clock_in->format('H:i')
        );
        $response->assertDontSeeText(
            $currentAttendance->clock_out->format('H:i')
        );

        $response->assertSeeText(
            $previousAttendance->work_date->isoFormat('MM/DD(ddd)')
        );
        $response->assertSeeText(
            $previousAttendance->clock_in->format('H:i')
        );
        $response->assertSeeText(
            $previousAttendance->clock_out->format('H:i')
        );

        $response->assertDontSeeText(
            $nextAttendance->work_date->isoFormat('MM/DD(ddd)')
        );
        $response->assertDontSeeText(
            $nextAttendance->clock_in->format('H:i')
        );
        $response->assertDontSeeText(
            $nextAttendance->clock_out->format('H:i')
        );
    }

    /**
     * 項目: ユーザー情報取得機能 (管理者)
     * 内容: 「翌月」を押下した時に表示月の翌月の情報が表示される
     */
    public function test_admin_can_view_next_month_attendance(): void
    {
        // 前月・当月・翌月の日付を準備
        $currentMonth = now()->startOfMonth();
        $previousMonth = $currentMonth->copy()->subMonthNoOverflow();
        $nextMonth = $currentMonth->copy()->addMonthNoOverflow();

        // 勤怠を作成
        $currentAttendance = Attendance::factory()
            ->for($this->user)
            ->forWorkDate($currentMonth)
            ->create([
                'clock_in' => $currentMonth->copy()->setTime(1, 0, 0),
                'clock_out' => $currentMonth->copy()->setTime(2, 0, 0),
            ]);

        $previousAttendance = Attendance::factory()
            ->for($this->user)
            ->forWorkDate($previousMonth)
            ->create([
                'clock_in' => $previousMonth->copy()->setTime(3, 0, 0),
                'clock_out' => $previousMonth->copy()->setTime(4, 0, 0),
            ]);

        $nextAttendance = Attendance::factory()
            ->for($this->user)
            ->forWorkDate($nextMonth)
            ->create([
                'clock_in' => $nextMonth->copy()->setTime(5, 0, 0),
                'clock_out' => $nextMonth->copy()->setTime(6, 0, 0),
            ]);

        // 管理者でログインして、勤怠一覧を開く
        $this->actingAs($this->admin)
            ->get(route('admin.attendance.staff.monthly', [
                'id' => $this->user->id
            ]))
            ->assertOk();

        // 翌月ボタンを押す
        $response = $this->actingAs($this->admin)
            ->get(route('admin.attendance.staff.monthly', [
                'id' => $this->user->id,
                'month' => $nextMonth->format('Y-m'),
            ]));

        $response->assertOk();

        // 翌月が表示されている事を確認
        $response->assertSeeText($nextMonth->format('Y/m'));

        // 翌月のデータのみ表示されている事を確認
        $response->assertDontSeeText(
            $currentAttendance->work_date->isoFormat('MM/DD(ddd)')
        );
        $response->assertDontSeeText(
            $currentAttendance->clock_in->format('H:i')
        );
        $response->assertDontSeeText(
            $currentAttendance->clock_out->format('H:i')
        );

        $response->assertDontSeeText(
            $previousAttendance->work_date->isoFormat('MM/DD(ddd)')
        );
        $response->assertDontSeeText(
            $previousAttendance->clock_in->format('H:i')
        );
        $response->assertDontSeeText(
            $previousAttendance->clock_out->format('H:i')
        );

        $response->assertSeeText(
            $nextAttendance->work_date->isoFormat('MM/DD(ddd)')
        );
        $response->assertSeeText(
            $nextAttendance->clock_in->format('H:i')
        );
        $response->assertSeeText(
            $nextAttendance->clock_out->format('H:i')
        );
    }


    /**
     * 項目: ユーザー情報取得機能 (管理者)
     * 内容: 「詳細」を押下すると、その日の勤怠詳細画面に遷移する
     */
    public function test_admin_can_navigate_to_attendance_detail_from_list(): void
    {
        $currentMonth = now()->startOfMonth();

        // 勤怠を作成
        $attendance = Attendance::factory()
            ->for($this->user)
            ->forWorkDate($currentMonth)
            ->create();

        // 管理者でログインして、勤怠一覧を開く
        $this->actingAs($this->admin)
            ->get(route('admin.attendance.staff.monthly', [
                'id' => $this->user->id
            ]))
            ->assertOk();

        // 詳細ボタンを押すと勤怠詳細画面に遷移する事を確認
        $response = $this->actingAs($this->admin)
            ->get(route('admin.attendance.detail', [
                'id' => $attendance->id,
            ]));

        $response->assertOk();

        // 名前の確認
        $response->assertSeeInOrder([
            'data-testid="user-name"',
            $this->user->name,
        ], false);

        // 日付の確認
        $response->assertSeeInOrder([
            'data-testid="work-date-year"',
            $currentMonth->format('Y') . '年',
        ], false);

        $response->assertSeeInOrder([
            'data-testid="work-date-month-day"',
            $currentMonth->format('n月j日'),
        ], false);
    }
}
