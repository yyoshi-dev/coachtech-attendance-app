<?php

namespace Tests\Feature\Admin;

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
    protected User $admin;
    protected Carbon $workDate;

    protected function setUp(): void
    {
        parent::setUp();

        $this->user = User::factory()->create();
        $this->admin = User::factory()
            ->create(['role' => 'admin']);

        $this->workDate = Carbon::parse('2026-04-15');
    }

    /**
     * 勤怠作成用の関数
     */
    private function createAttendance(
        User $user,
        Carbon $workDate,
        ?Carbon $clockIn = null,
        ?Carbon $clockOut = null,
    ): Attendance
    {
        return Attendance::factory()
            ->for($user)
            ->forWorkDate($workDate)
            ->create([
                'clock_in' => $clockIn ?? $workDate->copy()->setTime(9, 00, 00),
                'clock_out' => $clockOut ?? $workDate->copy()->setTime(18, 00, 00),
            ]);
    }

    /**
     * 項目: 勤怠詳細情報取得・修正機能 (管理者)
     * 内容: 勤怠詳細画面に表示されるデータが選択したものになっている
     */
    public function test_admin_can_view_attendance_detail(): void
    {
        $breakStart = $this->workDate->copy()->setTime(12, 00, 00);
        $breakEnd = $this->workDate->copy()->setTime(13, 00, 00);
        $secondBreakStart = $this->workDate->copy()->setTime(15, 00, 00);
        $secondBreakEnd = $this->workDate->copy()->setTime(15, 15, 00);

        // 勤怠を作成
        $attendance = $this->createAttendance(
            $this->user,
            $this->workDate,
        );

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

        // 管理者にログインして、勤怠詳細ページを開く
        $response = $this->actingAs($this->admin)
            ->get(route('admin.attendance.detail', [
                'id' => $attendance->id
            ]));

        $response->assertOk();

        // 詳細画面の内容が選択した情報と一致する事を確認
        // 名前の確認
        $response->assertSeeInOrder([
            'data-testid="user-name"',
            $this->user->name,
        ], false);

        // 日付の確認
        $response->assertSeeInOrder([
            'data-testid="work-date-year"',
            $this->workDate->format('Y') . '年',
        ], false);

        $response->assertSeeInOrder([
            'data-testid="work-date-month-day"',
            $this->workDate->format('n月j日'),
        ], false);

        // 出勤・退勤の確認
        $response->assertSeeInOrder([
            'data-testid="requested-clock-in-input"',
            $attendance->clock_in->format('H:i'),
        ], false);

        $response->assertSeeInOrder([
            'data-testid="requested-clock-out-input"',
            $attendance->clock_out->format('H:i'),
        ], false);

        // 休憩の確認
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
     * 項目: 勤怠詳細情報取得・修正機能 (管理者)
     * 内容: 出勤時間が退勤時間より後になっている場合、エラーメッセージが表示される
     */
    public function test_clock_in_after_clock_out_shows_error_message(): void
    {
        // 勤怠を作成
        $attendance = $this->createAttendance(
            $this->user,
            $this->workDate,
        );

        // 管理者にログインして、勤怠詳細ページを開く
        $this->actingAs($this->admin)
            ->get(route('admin.attendance.detail', [
                'id' => $attendance->id
            ]))
            ->assertOk();

        // 出勤時間を退勤時間より後に設定した修正データを作成
        $correctionData = [
            'requested_clock_in' => '19:00',
            'requested_clock_out' => '18:00',
            'request_remarks' => 'test',
        ];

        // 修正処理の実施
        $response = $this->actingAs($this->admin)
            ->put(
                route('admin.attendance.update', ['id' => $attendance->id]),
                $correctionData
            );

        // バリデーションメッセージを確認
        $response->assertSessionHasErrors([
            'requested_clock_in' => '出勤時間もしくは退勤時間が不適切な値です',
            'requested_clock_out' => '出勤時間もしくは退勤時間が不適切な値です',
        ]);

        // 勤怠データが更新されていない事を確認
        $this->assertDatabaseHas('attendances', [
            'id' => $attendance->id,
            'clock_in' => $attendance->clock_in->format('Y-m-d H:i:s'),
            'clock_out' => $attendance->clock_out->format('Y-m-d H:i:s'),
        ]);

        // DBに修正データが登録されていない事を確認
        $this->assertDatabaseCount('attendance_correction_requests', 0);
    }

    /**
     * 項目: 勤怠詳細情報取得・修正機能 (管理者)
     * 内容: 休憩開始時間が退勤時間より後になっている場合、エラーメッセージが表示される
     */
    public function test_break_start_after_clock_out_shows_error_message(): void
    {
        // 勤怠を作成
        $attendance = $this->createAttendance(
            $this->user,
            $this->workDate,
        );

        // 休憩を作成
        $break = AttendanceBreak::factory()
            ->for($attendance)
            ->create([
                'break_start' => $this->workDate->copy()->setTime(12, 00, 00),
                'break_end' => $this->workDate->copy()->setTime(13, 00, 00),
            ]);

        // 管理者にログインして、勤怠詳細ページを開く
        $this->actingAs($this->admin)
            ->get(route('admin.attendance.detail', [
                'id' => $attendance->id
            ]))
            ->assertOk();

        // 休憩開始時間を退勤時間より後に設定した修正データを作成
        $correctionData = [
            'requested_clock_in' => '09:00',
            'requested_clock_out' => '18:00',
            'request_remarks' => 'test',
            'attendance_break_id' => [0 => $break->id],
            'requested_break_start' => [0 => '19:00'],
            'requested_break_end' => [0 => '13:00'],
        ];

        // 修正処理の実施
        $response = $this->actingAs($this->admin)
            ->put(
                route('admin.attendance.update', ['id' => $attendance->id]),
                $correctionData
            );

        // バリデーションメッセージを確認
        $response->assertSessionHasErrors([
            'requested_break_start.0' => '休憩時間が不適切な値です',
        ]);

        // 休憩データが更新されていない事を確認
        $this->assertDatabaseHas('attendance_breaks', [
            'id' => $break->id,
            'break_start' => $break->break_start->format('Y-m-d H:i:s'),
            'break_end' => $break->break_end->format('Y-m-d H:i:s'),
        ]);

        // DBに修正データが登録されていない事を確認
        $this->assertDatabaseCount('attendance_correction_requests', 0);
        $this->assertDatabaseCount('attendance_correction_request_breaks', 0);
    }

    /**
     * 項目: 勤怠詳細情報取得・修正機能 (管理者)
     * 内容: 休憩終了時間が退勤時間より後になっている場合、エラーメッセージが表示される
     */
    public function test_break_end_after_clock_out_shows_error_message(): void
    {
        // 勤怠を作成
        $attendance = $this->createAttendance(
            $this->user,
            $this->workDate,
        );

        // 休憩を作成
        $break = AttendanceBreak::factory()
            ->for($attendance)
            ->create([
                'break_start' => $this->workDate->copy()->setTime(12, 00, 00),
                'break_end' => $this->workDate->copy()->setTime(13, 00, 00),
            ]);

        // 管理者にログインして、勤怠詳細ページを開く
        $this->actingAs($this->admin)
            ->get(route('admin.attendance.detail', [
                'id' => $attendance->id
            ]))
            ->assertOk();

        // 休憩終了時間を退勤時間より後に設定した修正データを作成
        $correctionData = [
            'requested_clock_in' => '09:00',
            'requested_clock_out' => '18:00',
            'request_remarks' => 'test',
            'attendance_break_id' => [0 => $break->id],
            'requested_break_start' => [0 => '12:00'],
            'requested_break_end' => [0 => '19:00'],
        ];

        // 修正処理の実施
        $response = $this->actingAs($this->admin)
            ->put(
                route('admin.attendance.update', ['id' => $attendance->id]),
                $correctionData
            );

        // バリデーションメッセージを確認
        $response->assertSessionHasErrors([
            'requested_break_end.0' => '休憩時間もしくは退勤時間が不適切な値です',
        ]);

        // 休憩データが更新されていない事を確認
        $this->assertDatabaseHas('attendance_breaks', [
            'id' => $break->id,
            'break_start' => $break->break_start->format('Y-m-d H:i:s'),
            'break_end' => $break->break_end->format('Y-m-d H:i:s'),
        ]);

        // DBに修正データが登録されていない事を確認
        $this->assertDatabaseCount('attendance_correction_requests', 0);
        $this->assertDatabaseCount('attendance_correction_request_breaks', 0);
    }

    /**
     * 項目: 勤怠詳細情報取得・修正機能 (管理者)
     * 内容: 備考欄が未入力の場合のエラーメッセージが表示される
     */
    public function test_empty_remarks_shows_error_message(): void
    {
        // 勤怠を作成
        $attendance = $this->createAttendance(
            $this->user,
            $this->workDate,
        );

        // 管理者にログインして、勤怠詳細ページを開く
        $this->actingAs($this->admin)
            ->get(route('admin.attendance.detail', [
                'id' => $attendance->id
            ]))
            ->assertOk();

        // 備考欄を未入力に設定した修正データを作成
        $correctionData = [
            'requested_clock_in' => '05:00',
            'requested_clock_out' => '13:00',
            'request_remarks' => '',
        ];

        // 修正処理の実施
        $response = $this->actingAs($this->admin)
            ->put(
                route('admin.attendance.update', ['id' => $attendance->id]),
                $correctionData
            );

        // バリデーションメッセージを確認
        $response->assertSessionHasErrors([
            'request_remarks' => '備考を記入してください'
        ]);

        // 勤怠データが更新されていない事を確認
        $this->assertDatabaseHas('attendances', [
            'id' => $attendance->id,
            'clock_in' => $attendance->clock_in->format('Y-m-d H:i:s'),
            'clock_out' => $attendance->clock_out->format('Y-m-d H:i:s'),
        ]);

        // DBに修正データが登録されていない事を確認
        $this->assertDatabaseCount('attendance_correction_requests', 0);
    }
}
