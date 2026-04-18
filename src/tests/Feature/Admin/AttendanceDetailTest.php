<?php

namespace Tests\Feature\Admin;

use App\Models\Attendance;
use App\Models\AttendanceBreak;
use App\Models\AttendanceCorrectionRequest;
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

    /**
     * 項目: 勤怠詳細情報取得・修正機能 (管理者)
     * 内容: 直接修正処理が実行される
     */
    public function test_admin_can_update_attendance(): void
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

        // 勤怠・休憩データがそれぞれ1件である事を確認
        $this->assertDatabaseCount('attendances', 1);
        $this->assertDatabaseCount('attendance_breaks', 1);

        // 修正データが0件である事を確認
        $this->assertDatabaseCount('attendance_correction_requests', 0);
        $this->assertDatabaseCount('attendance_correction_request_breaks', 0);

        // 管理者にログインして、勤怠詳細ページを開く
        $this->actingAs($this->admin)
            ->get(route('admin.attendance.detail', [
                'id' => $attendance->id
            ]))
            ->assertOk();

        // 修正データを作成
        $correctionData = [
            'requested_clock_in' => '09:15',
            'requested_clock_out' => '18:15',
            'request_remarks' => 'test',
            'attendance_break_id' => [
                0 => $break->id,
                1 => null,
            ],
            'requested_break_start' => [
                0 => '12:15',
                1 => '15:00',
            ],
            'requested_break_end' => [
                0 => '13:15',
                1 => '15:15',
            ],
        ];

        // 修正処理の実施
        $this->actingAs($this->admin)
            ->put(
                route('admin.attendance.update', ['id' => $attendance->id]),
                $correctionData
            )
            ->assertRedirect(route('admin.attendance.staff.monthly', [
                'id' => $attendance->user_id,
                'month' => $attendance->work_date->format('Y-m'),
            ]));

        // 勤怠データが1件のまま、休憩データが1件追加された事を確認
        $this->assertDatabaseCount('attendances', 1);
        $this->assertDatabaseCount('attendance_breaks', 2);

        $date = $this->workDate->toDateString();

        // 勤怠が更新された事を確認
        $this->assertDatabaseHas('attendances', [
            'id' => $attendance->id,
            'clock_in' => Carbon::parse(
                "$date {$correctionData['requested_clock_in']}"
            )->toDateTimeString(),
            'clock_out' => Carbon::parse(
                "$date {$correctionData['requested_clock_out']}"
            )->toDateTimeString(),
        ]);

        // 休憩が更新された事を確認
        $this->assertDatabaseHas('attendance_breaks', [
            'id' => $break->id,
            'attendance_id' => $attendance->id,
            'break_start' => Carbon::parse(
                "$date {$correctionData['requested_break_start'][0]}"
            )->toDateTimeString(),
            'break_end' => Carbon::parse(
                "$date {$correctionData['requested_break_end'][0]}"
            )->toDateTimeString(),
            'sort_order' => 1,
        ]);

        $this->assertDatabaseHas('attendance_breaks', [
            'attendance_id' => $attendance->id,
            'break_start' => Carbon::parse(
                "$date {$correctionData['requested_break_start'][1]}"
            )->toDateTimeString(),
            'break_end' => Carbon::parse(
                "$date {$correctionData['requested_break_end'][1]}"
            )->toDateTimeString(),
            'sort_order' => 2,
        ]);

        // 修正データが作成された事を確認
        $this->assertDatabaseCount('attendance_correction_requests', 1);
        $this->assertDatabaseCount('attendance_correction_request_breaks', 2);

        // 修正データの取得
        $correction = AttendanceCorrectionRequest::with([
            'attendanceCorrectionRequestBreaks',
            'attendance.user',
            'attendance.attendanceBreaks'
        ])
        ->first();

        // 修正データの中身を確認
        $this->assertDatabaseHas('attendance_correction_requests', [
            'attendance_id' => $attendance->id,
            'request_user_id' => $this->admin->id,
            'requested_clock_in' => Carbon::parse(
                "$date {$correctionData['requested_clock_in']}"
            )->toDateTimeString(),
            'requested_clock_out' => Carbon::parse(
                "$date {$correctionData['requested_clock_out']}"
            )->toDateTimeString(),
            'request_remarks' => $correctionData['request_remarks'],
            'status' => 'approved',
            'reviewer_id' => $this->admin->id,
        ]);

        $this->assertDatabaseMissing('attendance_correction_requests', [
            'attendance_id' => $attendance->id,
            'reviewed_at' => null,
        ]);

        foreach ($correctionData['requested_break_start'] as $index => $breakStart) {
            $this->assertDatabaseHas('attendance_correction_request_breaks', [
                'attendance_correction_request_id' => $correction->id,
                'attendance_break_id' => $correctionData['attendance_break_id'][$index],
                'requested_break_start' => Carbon::parse(
                    "$date {$breakStart}"
                )->toDateTimeString(),
                'requested_break_end' => Carbon::parse(
                    "$date {$correctionData['requested_break_end'][$index]}"
                )->toDateTimeString(),
                'sort_order' => $index + 1,
            ]);
        }

        // 申請一覧画面 (管理者)の承認済みページに表示される事を確認
        $response = $this->actingAs($this->admin)
            ->get(route('attendance.corrections.index', [
                'tab' => 'approved',
            ]));

        $response->assertOk();

        $response->assertSeeInOrder([
            $this->user->name,
            $this->workDate->format('Y/m/d'),
            $correction->request_remarks,
            Carbon::parse($correction->created_at)->format('Y/m/d'),
        ], false);
    }

    /**
     * 項目: 勤怠詳細情報取得・修正機能 (管理者)
     * 内容: (オプション) 承認待ちの状態では直接修正できない
     */
    public function test_admin_cannot_update_when_request_is_pending(): void
    {
        // 勤怠を作成
        $attendance = $this->createAttendance($this->user, $this->workDate);

        // 修正データを作成
        $correction = AttendanceCorrectionRequest::factory()
            ->forAttendance($attendance)
            ->create();

        // 修正データが1件である事を確認
        $this->assertDatabaseCount('attendance_correction_requests', 1);

        // 再修正データを作成
        $correctionData = [
            'requested_clock_in' => '09:15',
            'requested_clock_out' => '18:15',
            'request_remarks' => 'test',
        ];

        // 修正処理の実施
        $this->actingAs($this->admin)
            ->put(
                route('admin.attendance.update', ['id' => $attendance->id]),
                $correctionData
            )
            ->assertRedirect(route('admin.attendance.detail', ['id' => $attendance->id]));

        // 勤怠データが更新されていない事を確認
        $this->assertDatabaseHas('attendances', [
            'id' => $attendance->id,
            'clock_in' => $attendance->clock_in->format('Y-m-d H:i:s'),
            'clock_out' => $attendance->clock_out->format('Y-m-d H:i:s'),
        ]);

        // 修正データが追加作成されていない事を確認
        $this->assertDatabaseCount('attendance_correction_requests', 1);

        // 修正データの中身を確認
        $this->assertDatabaseHas('attendance_correction_requests', [
            'id' => $correction->id,
            'attendance_id' => $correction->attendance_id,
            'request_user_id' => $correction->request_user_id,
            'requested_clock_in' => $correction->requested_clock_in,
            'requested_clock_out' => $correction->requested_clock_out,
            'request_remarks' => $correction->request_remarks,
            'status' => $correction->status,
            'reviewer_id' => $correction->reviewer_id,
        ]);
    }

    /**
     * 項目: 勤怠詳細情報取得・修正機能 (管理者)
     * 内容: (オプション) 出勤時間が未入力の場合、エラーメッセージが表示される
     */
    public function test_empty_clock_in_shows_error_message(): void
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

        // 出勤時間を未入力に設定した修正データを作成
        $correctionData = [
            'requested_clock_in' => '',
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
            'requested_clock_in' => '出勤時間を入力してください',
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
     * 内容: (オプション) 退勤時間が未入力の場合、エラーメッセージが表示される
     */
    public function test_empty_clock_out_shows_error_message(): void
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

        // 退勤時間を未入力に設定した修正データを作成
        $correctionData = [
            'requested_clock_in' => '09:00',
            'requested_clock_out' => '',
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
            'requested_clock_out' => '退勤時間を入力してください',
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
     * 内容: (オプション) 休憩開始時間が未入力の場合、エラーメッセージが表示される
     */
    public function test_empty_break_start_shows_error_message(): void
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

        // 休憩開始時間を未入力に設定した修正データを作成
        $correctionData = [
            'requested_clock_in' => '09:00',
            'requested_clock_out' => '18:00',
            'request_remarks' => 'test',
            'attendance_break_id' => [0 => $break->id],
            'requested_break_start' => [0 => ''],
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
            'requested_break_start.0' => '休憩開始時間を入力してください',
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
     * 内容: (オプション) 休憩終了時間が未入力の場合、エラーメッセージが表示される
     */
    public function test_empty_break_end_shows_error_message(): void
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

        // 休憩終了時間を未入力に設定した修正データを作成
        $correctionData = [
            'requested_clock_in' => '09:00',
            'requested_clock_out' => '18:00',
            'request_remarks' => 'test',
            'attendance_break_id' => [0 => $break->id],
            'requested_break_start' => [0 => '12:00'],
            'requested_break_end' => [0 => ''],
        ];

        // 修正処理の実施
        $response = $this->actingAs($this->admin)
            ->put(
                route('admin.attendance.update', ['id' => $attendance->id]),
                $correctionData
            );

        // バリデーションメッセージを確認
        $response->assertSessionHasErrors([
            'requested_break_end.0' => '休憩終了時間を入力してください',
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
     * 内容: (オプション) 休憩開始時間が出勤時間より前になっている場合、エラーメッセージが表示される
     */
    public function test_break_start_before_clock_in_shows_error_message(): void
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

        // 休憩開始時間を出勤時間より前に設定した修正データを作成
        $correctionData = [
            'requested_clock_in' => '09:00',
            'requested_clock_out' => '18:00',
            'request_remarks' => 'test',
            'attendance_break_id' => [0 => $break->id],
            'requested_break_start' => [0 => '07:00'],
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
            'requested_break_start.0' => '休憩時間が不適切な値です'
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
     * 内容: (オプション) 休憩終了時間が出勤時間より前になっている場合、エラーメッセージが表示される
     */
    public function test_break_end_before_clock_in_shows_error_message(): void
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

        // 休憩終了時間を出勤時間より前に設定した修正データを作成
        $correctionData = [
            'requested_clock_in' => '09:00',
            'requested_clock_out' => '18:00',
            'request_remarks' => 'test',
            'attendance_break_id' => [0 => $break->id],
            'requested_break_start' => [0 => '12:00'],
            'requested_break_end' => [0 => '07:00'],
        ];

        // 修正処理の実施
        $response = $this->actingAs($this->admin)
            ->put(
                route('admin.attendance.update', ['id' => $attendance->id]),
                $correctionData
            );

        // バリデーションメッセージを確認
        $response->assertSessionHasErrors([
            'requested_break_end.0' => '休憩時間が不適切な値です'
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
     * 内容: (オプション) 休憩開始時間が休憩終了時間より後になっている場合、エラーメッセージが表示される
     */
    public function test_break_start_after_break_end_shows_error_message(): void
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

        // 休憩開始時間を休憩終了時間より後に設定した修正データを作成
        $correctionData = [
            'requested_clock_in' => '09:00',
            'requested_clock_out' => '18:00',
            'request_remarks' => 'test',
            'attendance_break_id' => [0 => $break->id],
            'requested_break_start' => [0 => '13:00'],
            'requested_break_end' => [0 => '12:00'],
        ];

        // 修正処理の実施
        $response = $this->actingAs($this->admin)
            ->put(
                route('admin.attendance.update', ['id' => $attendance->id]),
                $correctionData
            );

        // バリデーションメッセージを確認
        $response->assertSessionHasErrors([
            'requested_break_start.0' => '休憩開始時間もしくは休憩終了時間が不適切な値です',
            'requested_break_end.0' => '休憩開始時間もしくは休憩終了時間が不適切な値です',
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
}
