<?php

namespace Tests\Feature\User;

use App\Models\Attendance;
use App\Models\AttendanceBreak;
use App\Models\AttendanceCorrectionRequest;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AttendanceCorrectionTest extends TestCase
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
     * 項目: 勤怠詳細情報修正機能 (一般ユーザー)
     * 内容: 出勤時間が退勤時間より後になっている場合、エラーメッセージが表示される
     */
    public function test_clock_in_after_clock_out_shows_error_message(): void
    {
        // 勤怠を作成
        $attendance = $this->createAttendance(
            $this->user,
            $this->workDate,
        );

        // ログインして勤怠詳細ページを開く
        $this->actingAs($this->user)
            ->get(route('attendance.detail', ['id' => $attendance->id]))
            ->assertOk();

        // 出勤時間を退勤時間より後に設定した修正データを作成
        $correctionData = [
            'requested_clock_in' => '19:00',
            'requested_clock_out' => '18:00',
            'request_remarks' => 'test',
        ];

        // 修正処理の実施
        $response = $this->actingAs($this->user)
            ->post(
                route('attendance.corrections.store', ['id' => $attendance->id]),
                $correctionData
            );

        // バリデーションメッセージを確認
        $response->assertSessionHasErrors([
            'requested_clock_in' => '出勤時間もしくは退勤時間が不適切な値です'
        ]);

        // DBに修正データが登録されていない事を確認
        $this->assertDatabaseCount('attendance_correction_requests', 0);
    }

    /**
     * 項目: 勤怠詳細情報修正機能 (一般ユーザー)
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

        // ログインして勤怠詳細ページを開く
        $this->actingAs($this->user)
            ->get(route('attendance.detail', ['id' => $attendance->id]))
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
        $response = $this->actingAs($this->user)
            ->post(
                route('attendance.corrections.store', ['id' => $attendance->id]),
                $correctionData
            );

        // バリデーションメッセージを確認
        $response->assertSessionHasErrors([
            'requested_break_start.0' => '休憩時間が不適切な値です'
        ]);

        // DBに修正データが登録されていない事を確認
        $this->assertDatabaseCount('attendance_correction_requests', 0);
        $this->assertDatabaseCount('attendance_correction_request_breaks', 0);
    }

    /**
     * 項目: 勤怠詳細情報修正機能 (一般ユーザー)
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

        // ログインして勤怠詳細ページを開く
        $this->actingAs($this->user)
            ->get(route('attendance.detail', ['id' => $attendance->id]))
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
        $response = $this->actingAs($this->user)
            ->post(
                route('attendance.corrections.store', ['id' => $attendance->id]),
                $correctionData
            );

        // バリデーションメッセージを確認
        $response->assertSessionHasErrors([
            'requested_break_end.0' => '休憩時間もしくは退勤時間が不適切な値です'
        ]);

        // DBに修正データが登録されていない事を確認
        $this->assertDatabaseCount('attendance_correction_requests', 0);
        $this->assertDatabaseCount('attendance_correction_request_breaks', 0);
    }

    /**
     * 項目: 勤怠詳細情報修正機能 (一般ユーザー)
     * 内容: 備考欄が未入力の場合のエラーメッセージが表示される
     */
    public function test_empty_remarks_shows_error_message(): void
    {
        // 勤怠を作成
        $attendance = $this->createAttendance(
            $this->user,
            $this->workDate,
        );

        // ログインして勤怠詳細ページを開く
        $this->actingAs($this->user)
            ->get(route('attendance.detail', ['id' => $attendance->id]))
            ->assertOk();

        // 備考欄を未入力に設定した修正データを作成
        $correctionData = [
            'requested_clock_in' => '09:00',
            'requested_clock_out' => '18:00',
            'request_remarks' => '',
        ];

        // 修正処理の実施
        $response = $this->actingAs($this->user)
            ->post(
                route('attendance.corrections.store', ['id' => $attendance->id]),
                $correctionData
            );

        // バリデーションメッセージを確認
        $response->assertSessionHasErrors([
            'request_remarks' => '備考を記入してください'
        ]);

        // DBに修正データが登録されていない事を確認
        $this->assertDatabaseCount('attendance_correction_requests', 0);
    }

    /**
     * 項目: 勤怠詳細情報修正機能 (一般ユーザー)
     * 内容: 修正申請処理が実行される
     */
    public function test_user_can_apply_attendance_correction(): void
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

        // 修正データが0件である事を確認
        $this->assertDatabaseCount('attendance_correction_requests', 0);
        $this->assertDatabaseCount('attendance_correction_request_breaks', 0);

        // ログインして勤怠詳細ページを開く
        $this->actingAs($this->user)
            ->get(route('attendance.detail', ['id' => $attendance->id]))
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
        $this->actingAs($this->user)
            ->post(
                route('attendance.corrections.store', ['id' => $attendance->id]),
                $correctionData
            )
            ->assertRedirect(route('attendance.corrections.index'));

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
        $date = $this->workDate->toDateString();

        $this->assertDatabaseHas('attendance_correction_requests', [
            'attendance_id' => $attendance->id,
            'request_user_id' => $this->user->id,
            'requested_clock_in' => Carbon::parse(
                "$date {$correctionData['requested_clock_in']}"
            )->toDateTimeString(),
            'requested_clock_out' => Carbon::parse(
                "$date {$correctionData['requested_clock_out']}"
            )->toDateTimeString(),
            'request_remarks' => $correctionData['request_remarks'],
            'status' => 'pending',
            'reviewed_at' => null,
            'reviewer_id' => null,
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

        // 申請一覧画面 (管理者)に表示される事を確認
        $response = $this->actingAs($this->admin)
            ->get(route('attendance.corrections.index'));

        $response->assertOk();

        $response->assertSeeInOrder([
            $this->user->name,
            $this->workDate->format('Y/m/d'),
            $correction->request_remarks,
            Carbon::parse($correction->created_at)->format('Y/m/d'),
        ], false);

        // 管理者の承認画面に表示される事を確認
        $responseDetail = $this->actingAs($this->admin)
            ->get(route('admin.attendance.correction.detail', [
                'attendance_correct_request_id' => $correction->id
            ]));

        $responseDetail->assertOk();

        $responseDetail->assertSeeInOrder([
            'data-testid="user-name"',
            $correction->attendance->user->name,
        ], false);

        $responseDetail->assertSeeInOrder([
            'data-testid="work-date-year"',
            $correction->attendance->work_date->format('Y') . '年',
        ], false);
        $responseDetail->assertSeeInOrder([
            'data-testid="work-date-month-day"',
            $correction->attendance->work_date->format('n月j日'),
        ], false);

        $responseDetail->assertSeeInOrder([
            'data-testid="requested-clock-in-text"',
            $correction->requested_clock_in->format('H:i'),
        ], false);
        $responseDetail->assertSeeInOrder([
            'data-testid="requested-clock-out-text"',
            $correction->requested_clock_out->format('H:i'),
        ], false);

        foreach ($correction->attendanceCorrectionRequestBreaks as $index => $break) {
            $responseDetail->assertSeeInOrder([
                'data-testid="requested-break-start-text-' . $index . '"',
                $break->requested_break_start->format('H:i'),
            ], false);
            $responseDetail->assertSeeInOrder([
                'data-testid="requested-break-end-text-' . $index . '"',
                $break->requested_break_end->format('H:i'),
            ], false);
        }
    }

    /**
     * 項目: 勤怠詳細情報修正機能 (一般ユーザー)
     * 内容: 「承認待ち」にログインユーザーが行った申請が全て表示されていること
     */
    public function test_only_logged_in_user_pending_corrections_are_displayed(): void
    {
        // 他のユーザーを作成
        $other = User::factory()->create();

        // 勤怠を作成
        $ownPendingAttendances = collect([
            $this->createAttendance($this->user, $this->workDate),
            $this->createAttendance($this->user, $this->workDate->copy()->subDay()),
        ]);

        $ownApprovedAttendances = collect([
            $this->createAttendance($this->user, $this->workDate->copy()->subDays(5)),
            $this->createAttendance($this->user, $this->workDate->copy()->subDays(10)),
        ]);

        $otherPendingAttendances = collect([
            $this->createAttendance($other, $this->workDate),
            $this->createAttendance($other, $this->workDate->copy()->subDay()),
        ]);

        // 修正データを作成
        $ownPendingCorrections = $ownPendingAttendances->map(function ($attendance) {
            return AttendanceCorrectionRequest::factory()
                ->forAttendance($attendance)
                ->create();
        });

        $ownApprovedCorrections = $ownApprovedAttendances->map(function ($attendance) {
            return AttendanceCorrectionRequest::factory()
                ->forAttendance($attendance)
                ->create([
                    'status' => 'approved',
                    'reviewed_at' => now(),
                    'reviewer_id' => $this->admin->id,
                ]);
        });

        $otherPendingCorrections = $otherPendingAttendances->map(function ($attendance) {
            return AttendanceCorrectionRequest::factory()
                ->forAttendance($attendance)
                ->create();
        });

        // 申請一覧画面の承認待ちタブを開く
        $response = $this->actingAs($this->user)
            ->get(route('attendance.corrections.index', ['tab' => 'pending']));

        $response->assertOk();

        // 自身の承認待ち申請が表示される事を確認
        foreach ($ownPendingCorrections as $correction) {
            $response->assertSee(
                'data-testid="correction-row-' . $correction->id . '"',
                false
            );
        }

        // 自身の承認済み申請が表示されない事を確認
        foreach ($ownApprovedCorrections as $correction) {
            $response->assertDontSee(
                'data-testid="correction-row-' . $correction->id . '"',
                false
            );
        }

        // 他ユーザーの承認待ち申請が表示されない事を確認
        foreach ($otherPendingCorrections as $correction) {
            $response->assertDontSee(
                'data-testid="correction-row-' . $correction->id . '"',
                false
            );
        }
    }

    /**
     * 項目: 勤怠詳細情報修正機能 (一般ユーザー)
     * 内容: 「承認済み」に管理者が承認した修正申請が全て表示されている
     */
    public function test_all_approved_corrections_are_displayed_in_approved_page(): void
    {
        // 他のユーザーを作成
        $other = User::factory()->create();

        // 勤怠を作成
        $ownPendingAttendances = collect([
            $this->createAttendance($this->user, $this->workDate),
            $this->createAttendance($this->user, $this->workDate->copy()->subDay()),
        ]);

        $ownApprovedAttendances = collect([
            $this->createAttendance($this->user, $this->workDate->copy()->subDays(5)),
            $this->createAttendance($this->user, $this->workDate->copy()->subDays(10)),
        ]);

        $otherApprovedAttendances = collect([
            $this->createAttendance($other, $this->workDate),
            $this->createAttendance($other, $this->workDate->copy()->subDay()),
        ]);

        // 修正データを作成
        $ownPendingCorrections = $ownPendingAttendances->map(function ($attendance) {
            return AttendanceCorrectionRequest::factory()
                ->forAttendance($attendance)
                ->create();
        });

        $ownApprovedCorrections = $ownApprovedAttendances->map(function ($attendance) {
            return AttendanceCorrectionRequest::factory()
                ->forAttendance($attendance)
                ->create([
                    'status' => 'approved',
                    'reviewed_at' => now(),
                    'reviewer_id' => $this->admin->id,
                ]);
        });

        $otherApprovedCorrections = $otherApprovedAttendances->map(function ($attendance) {
            return AttendanceCorrectionRequest::factory()
                ->forAttendance($attendance)
                ->create([
                    'status' => 'approved',
                    'reviewed_at' => now(),
                    'reviewer_id' => $this->admin->id,
                ]);
        });

        // 申請一覧画面の承認済みタブを開く
        $response = $this->actingAs($this->user)
            ->get(route('attendance.corrections.index', ['tab' => 'approved']));

        $response->assertOk();

        // 自身の承認待ち申請が表示されない事を確認
        foreach ($ownPendingCorrections as $correction) {
            $response->assertDontSee(
                'data-testid="correction-row-' . $correction->id . '"',
                false
            );
        }

        // 自身の承認済み申請が表示される事を確認
        foreach ($ownApprovedCorrections as $correction) {
            $response->assertSee(
                'data-testid="correction-row-' . $correction->id . '"',
                false
            );
        }

        // 他ユーザーの承認済み申請が表示されない事を確認
        foreach ($otherApprovedCorrections as $correction) {
            $response->assertDontSee(
                'data-testid="correction-row-' . $correction->id . '"',
                false
            );
        }
    }

    /**
     * 項目: 勤怠詳細情報修正機能 (一般ユーザー)
     * 内容: 各申請の「詳細」を押下すると勤怠詳細画面に遷移する
     */
    public function test_user_can_view_correction_detail_from_detail_button(): void
    {
        // 勤怠を作成
        $pendingAttendance = $this->createAttendance($this->user, $this->workDate);
        $approvedAttendance = $this->createAttendance($this->user, $this->workDate->copy()->subDays(5));

        // 修正データを作成
        $pendingCorrection = AttendanceCorrectionRequest::factory()
            ->forAttendance($pendingAttendance)
            ->create();

        $approvedCorrection = AttendanceCorrectionRequest::factory()
            ->forAttendance($approvedAttendance)
            ->create([
                'status' => 'approved',
                'reviewed_at' => now(),
                'reviewer_id' => $this->admin->id,
            ]);

        // 申請一覧画面の承認待ちタブを開く
        $this->actingAs($this->user)
            ->get(route('attendance.corrections.index', ['tab' => 'pending']))
            ->assertOk();

        // 詳細ボタンを押す
        $responsePending = $this->actingAs($this->user)
            ->get(route('attendance.detail', [
                'id' => $pendingCorrection->attendance_id,
                'correction_id' => $pendingCorrection->id,
            ]));

        $responsePending->assertOk();

        $responsePending->assertSeeInOrder([
            'data-testid="user-name"',
            $pendingCorrection->attendance->user->name,
        ], false);

        $responsePending->assertSeeInOrder([
            'data-testid="work-date-year"',
            $pendingCorrection->attendance->work_date->format('Y') . '年',
        ], false);
        $responsePending->assertSeeInOrder([
            'data-testid="work-date-month-day"',
            $pendingCorrection->attendance->work_date->format('n月j日'),
        ], false);

        $responsePending->assertSeeInOrder([
            'data-testid="requested-clock-in-text"',
            $pendingCorrection->requested_clock_in->format('H:i'),
        ], false);
        $responsePending->assertSeeInOrder([
            'data-testid="requested-clock-out-text"',
            $pendingCorrection->requested_clock_out->format('H:i'),
        ], false);
        $responsePending->assertSeeText('承認待ちのため修正はできません');

        // 申請一覧画面の承認済みタブを開く
        $this->actingAs($this->user)
            ->get(route('attendance.corrections.index', ['tab' => 'approved']))
            ->assertOk();

        // 詳細ボタンを押す
        $responseApproved = $this->actingAs($this->user)
            ->get(route('attendance.detail', [
                'id' => $approvedCorrection->attendance_id,
                'correction_id' => $approvedCorrection->id,
            ]));

        $responseApproved->assertOk();

        $responseApproved->assertSeeInOrder([
            'data-testid="user-name"',
            $approvedCorrection->attendance->user->name,
        ], false);

        $responseApproved->assertSeeInOrder([
            'data-testid="work-date-year"',
            $approvedCorrection->attendance->work_date->format('Y') . '年',
        ], false);
        $responseApproved->assertSeeInOrder([
            'data-testid="work-date-month-day"',
            $approvedCorrection->attendance->work_date->format('n月j日'),
        ], false);

        $responseApproved->assertSeeInOrder([
            'data-testid="requested-clock-in-text"',
            $approvedCorrection->requested_clock_in->format('H:i'),
        ], false);
        $responseApproved->assertSeeInOrder([
            'data-testid="requested-clock-out-text"',
            $approvedCorrection->requested_clock_out->format('H:i'),
        ], false);
        $responseApproved->assertSeeText('承認済み');
    }

    /**
     * 項目: 勤怠詳細情報修正機能 (一般ユーザー)
     * 内容: (オプション) 他の人の勤怠は修正申請できない
     */
    public function test_user_cannot_correct_other_user_attendance(): void
    {
        // 他のユーザーを作成
        $other = User::factory()->create();

        // 勤怠を作成
        $attendance = $this->createAttendance($other, $this->workDate);

        // 修正データが0件である事を確認
        $this->assertDatabaseCount('attendance_correction_requests', 0);

        // 修正データを作成
        $correctionData = [
            'requested_clock_in' => '09:15',
            'requested_clock_out' => '18:15',
            'request_remarks' => 'test',
        ];

        // 修正処理の実施
        $this->actingAs($this->user)
            ->post(
                route('attendance.corrections.store', ['id' => $attendance->id]),
                $correctionData
            )
            ->assertNotFound();

        // 修正データが作成されていない事を確認
        $this->assertDatabaseCount('attendance_correction_requests', 0);
    }

    /**
     * 項目: 勤怠詳細情報修正機能 (一般ユーザー)
     * 内容: (オプション) 承認待ちの状態で再申請できない
     */
    public function test_user_cannot_request_when_request_is_pending(): void
    {
        // 勤怠を作成
        $attendance = $this->createAttendance($this->user, $this->workDate);

        // 修正データを作成
        AttendanceCorrectionRequest::factory()
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
        $this->actingAs($this->user)
            ->post(
                route('attendance.corrections.store', ['id' => $attendance->id]),
                $correctionData
            )
            ->assertForbidden();

        // 修正データが追加作成されていない事を確認
        $this->assertDatabaseCount('attendance_correction_requests', 1);
    }
}
