<?php

namespace Tests\Feature\Admin;

use App\Models\Attendance;
use App\Models\AttendanceBreak;
use App\Models\AttendanceCorrectionRequest;
use App\Models\AttendanceCorrectionRequestBreak;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AttendanceCorrectionTest extends TestCase
{
    use RefreshDatabase;

    protected User $admin;
    protected Collection $users;

    protected function setUp(): void
    {
        parent::setUp();

        $this->admin = User::factory()
            ->create(['role' => 'admin']);

        $this->users = User::factory()->count(3)->create();
    }

    /**
     * 勤怠+休憩1件を作成する関数
     */
    private function createAttendanceWithBreak(
        User $user,
        Carbon $workDate,
        ?Carbon $clockIn = null,
        ?Carbon $clockOut = null,
        ?Carbon $breakStart = null,
        ?Carbon $breakEnd = null,
    ): Attendance
    {
        $attendance = Attendance::factory()
            ->for($user)
            ->forWorkDate($workDate)
            ->create([
                'clock_in' => $clockIn ?? $workDate->copy()->setTime(9, 00, 00),
                'clock_out' => $clockOut ?? $workDate->copy()->setTime(18, 00, 00),
            ]);

        AttendanceBreak::factory()
            ->forAttendance($attendance)
            ->create([
                'break_start' => $breakStart ?? $workDate->copy()->setTime(12, 00, 00),
                'break_end' => $breakEnd ?? $workDate->copy()->setTime(13, 00, 00),
            ]);

        return $attendance->refresh();
    }

    /**
     * 修正1件+休憩修正1件を作成する関数
     */
    private function createCorrectionWithBreak(
        Attendance $attendance,
        array $attributes = [],
    ): AttendanceCorrectionRequest
    {
        $correction = AttendanceCorrectionRequest::factory()
            ->forAttendance($attendance)
            ->create($attributes);

        AttendanceCorrectionRequestBreak::factory()
            ->forAttendanceCorrectionRequest(
                $correction,
                $attendance->attendanceBreaks->first(),
            )
            ->create();

        return $correction->refresh();
    }

    /**
     * 項目: 勤怠情報修正機能 (管理者)
     * 内容: 承認待ちの修正申請が全て表示されている
     */
    public function test_all_pending_correction_requests_are_displayed(): void
    {
        // 勤怠用の日付リストを作成
        $start = now()->subMonthNoOverflow()->startOfMonth();
        $end = $start->copy()->addDays(13);
        $dates = $start->daysUntil($end);

        // 勤怠を作成
        $attendances = collect();

        foreach ($this->users as $user) {
            foreach ($dates as $date) {
                $attendance = $this->createAttendanceWithBreak(
                    $user,
                    $date,
                );

                $attendances->push($attendance);
            }
        }

        // 承認待ち・承認済みの修正データ作成用に分割
        [$pendingAttendances, $approvedAttendances] = $attendances->split(2);

        // 承認待ち修正データを作成
        $pendingCorrections = collect();

        foreach ($pendingAttendances as $attendance) {
            $correction = $this->createCorrectionWithBreak(
                $attendance,
            );

            $pendingCorrections->push($correction);
        }

        // 承認済み修正データを作成
        $approvedCorrections = collect();

        foreach ($approvedAttendances as $attendance) {
            $correction = $this->createCorrectionWithBreak(
                $attendance,
                [
                    'status' => 'approved',
                    'reviewed_at' => now(),
                    'reviewer_id' => $this->admin->id,
                ]
            );

            $approvedCorrections->push($correction);
        }

        // 管理者としてログインして、修正一覧ページを開く
        $this->actingAs($this->admin)
            ->get(route('attendance.corrections.index'))
            ->assertOk();

        // 申請一覧画面の承認待ちタブを開く
        $response = $this->actingAs($this->admin)
            ->get(route('attendance.corrections.index', ['tab' => 'pending']));

        $response->assertOk();

        // 未承認の修正申請が表示される事を確認
        foreach ($pendingCorrections as $correction) {
            $response->assertSeeInOrder([
                'data-testid="correction-row-' . $correction->id . '"',
                $correction->statusLabel,
                $correction->attendance->user->name,
                $correction->attendance->work_date->format('Y/m/d'),
                $correction->request_remarks,
                $correction->created_at->format('Y/m/d'),
            ], false);
        }

        // 承認済みの修正申請は表示されない事を確認
        foreach ($approvedCorrections as $correction) {
            $response->assertDontSee(
                'data-testid="correction-row-' . $correction->id . '"',
                false
            );
        }
    }

    /**
     * 項目: 勤怠情報修正機能 (管理者)
     * 内容: 承認済みの修正申請が全て表示されている
     */
    public function test_all_approved_correction_requests_are_displayed(): void
    {
        // 勤怠用の日付リストを作成
        $start = now()->subMonthNoOverflow()->startOfMonth();
        $end = $start->copy()->addDays(13);
        $dates = $start->daysUntil($end);

        // 勤怠を作成
        $attendances = collect();

        foreach ($this->users as $user) {
            foreach ($dates as $date) {
                $attendance = $this->createAttendanceWithBreak(
                    $user,
                    $date,
                );

                $attendances->push($attendance);
            }
        }

        // 承認待ち・承認済みの修正データ作成用に分割
        [$pendingAttendances, $approvedAttendances] = $attendances->split(2);

        // 承認待ち修正データを作成
        $pendingCorrections = collect();

        foreach ($pendingAttendances as $attendance) {
            $correction = $this->createCorrectionWithBreak(
                $attendance,
            );

            $pendingCorrections->push($correction);
        }

        // 承認済み修正データを作成
        $approvedCorrections = collect();

        foreach ($approvedAttendances as $attendance) {
            $correction = $this->createCorrectionWithBreak(
                $attendance,
                [
                    'status' => 'approved',
                    'reviewed_at' => now(),
                    'reviewer_id' => $this->admin->id,
                ]
            );

            $approvedCorrections->push($correction);
        }

        // 管理者としてログインして、修正一覧ページを開く
        $this->actingAs($this->admin)
            ->get(route('attendance.corrections.index'))
            ->assertOk();

        // 申請一覧画面の承認済みタブを開く
        $response = $this->actingAs($this->admin)
            ->get(route('attendance.corrections.index', ['tab' => 'approved']));

        $response->assertOk();

        // 承認済みの修正申請が表示される事を確認
        foreach ($approvedCorrections as $correction) {
            $response->assertSeeInOrder([
                'data-testid="correction-row-' . $correction->id . '"',
                $correction->statusLabel,
                $correction->attendance->user->name,
                $correction->attendance->work_date->format('Y/m/d'),
                $correction->request_remarks,
                $correction->created_at->format('Y/m/d'),
            ], false);
        }

        // 未承認の修正申請は表示されない事を確認
        foreach ($pendingCorrections as $correction) {
            $response->assertDontSee(
                'data-testid="correction-row-' . $correction->id . '"',
                false
            );
        }
    }

    /**
     * 項目: 勤怠情報修正機能 (管理者)
     * 内容: 修正申請の詳細内容が正しく表示されている
     */
    public function test_admin_can_view_attendance_correction_request_detail(): void
    {
        $workDate = now()->subDays(5);

        /** @var User $user */
        $user = $this->users->first();

        // 勤怠を作成
        $pendingAttendance = $this->createAttendanceWithBreak(
            $user,
            $workDate,
        );

        $approvedAttendance = $this->createAttendanceWithBreak(
            $user,
            $workDate->copy()->subDay(),
        );

        // 修正データを作成
        $pendingCorrection = $this->createCorrectionWithBreak(
            $pendingAttendance,
        );

        $approvedCorrection = $this->createCorrectionWithBreak(
            $approvedAttendance,
            [
                'status' => 'approved',
                'reviewed_at' => now(),
                'reviewer_id' => $this->admin->id,
            ]
        );

        // 管理者としてログインして、承認待ちの申請の詳細画面を開く
        $pendingResponse = $this->actingAs($this->admin)
            ->get(route('admin.attendance.correction.detail', [
                'attendance_correct_request_id' => $pendingCorrection->id
            ]));

        $pendingResponse->assertOk();

        // 詳細画面の内容が選択した情報と一致する事を確認
        // 名前の確認
        $pendingResponse->assertSeeInOrder([
            'data-testid="user-name"',
            $user->name,
        ], false);

        // 日付の確認
        $pendingResponse->assertSeeInOrder([
            'data-testid="work-date-year"',
            $pendingAttendance->work_date->format('Y') . '年',
        ], false);

        $pendingResponse->assertSeeInOrder([
            'data-testid="work-date-month-day"',
            $pendingAttendance->work_date->format('n月j日'),
        ], false);

        // 出勤・退勤の確認
        $pendingResponse->assertSeeInOrder([
            'data-testid="requested-clock-in-text"',
            $pendingCorrection->requested_clock_in->format('H:i'),
        ], false);

        $pendingResponse->assertSeeInOrder([
            'data-testid="requested-clock-out-text"',
            $pendingCorrection->requested_clock_out->format('H:i'),
        ], false);

        // 休憩の確認
        foreach ($pendingCorrection->attendanceCorrectionRequestBreaks as $index => $break) {
            $pendingResponse->assertSeeInOrder([
                'data-testid="requested-break-start-text-' . $index . '"',
                $break->requested_break_start->format('H:i'),
            ], false);

            $pendingResponse->assertSeeInOrder([
                'data-testid="requested-break-end-text-' . $index . '"',
                $break->requested_break_end->format('H:i'),
            ], false);
        }

        // 備考の確認
        $pendingResponse->assertSeeInOrder([
            'data-testid="request-remarks-text"',
            $pendingCorrection->request_remarks,
        ], false);

        // 承認ボタンが表示され、承認済みが表示されない事を確認
        $pendingResponse->assertSeeText('承認');
        $pendingResponse->assertDontSeeText('承認済み');

        // 管理者としてログインして、承認済みの申請の詳細画面を開く
        $approvedResponse = $this->actingAs($this->admin)
            ->get(route('admin.attendance.correction.detail', [
                'attendance_correct_request_id' => $approvedCorrection->id
            ]));

        $approvedResponse->assertOk();

        // 詳細画面の内容が選択した情報と一致する事を確認
        // 名前の確認
        $approvedResponse->assertSeeInOrder([
            'data-testid="user-name"',
            $user->name,
        ], false);

        // 日付の確認
        $approvedResponse->assertSeeInOrder([
            'data-testid="work-date-year"',
            $approvedAttendance->work_date->format('Y') . '年',
        ], false);

        $approvedResponse->assertSeeInOrder([
            'data-testid="work-date-month-day"',
            $approvedAttendance->work_date->format('n月j日'),
        ], false);

        // 出勤・退勤の確認
        $approvedResponse->assertSeeInOrder([
            'data-testid="requested-clock-in-text"',
            $approvedCorrection->requested_clock_in->format('H:i'),
        ], false);

        $approvedResponse->assertSeeInOrder([
            'data-testid="requested-clock-out-text"',
            $approvedCorrection->requested_clock_out->format('H:i'),
        ], false);

        // 休憩の確認
        foreach ($approvedCorrection->attendanceCorrectionRequestBreaks as $index => $break) {
            $approvedResponse->assertSeeInOrder([
                'data-testid="requested-break-start-text-' . $index . '"',
                $break->requested_break_start->format('H:i'),
            ], false);

            $approvedResponse->assertSeeInOrder([
                'data-testid="requested-break-end-text-' . $index . '"',
                $break->requested_break_end->format('H:i'),
            ], false);
        }

        // 備考の確認
        $approvedResponse->assertSeeInOrder([
            'data-testid="request-remarks-text"',
            $approvedCorrection->request_remarks,
        ], false);

        // 承認済みが表示される事を確認
        $approvedResponse->assertSeeText('承認済み');
    }

    /**
     * 項目: 勤怠情報修正機能 (管理者)
     * 内容: 修正申請の承認処理が正しく行われる
     */
    public function test_admin_can_approve_user_attendance_correction_request(): void
    {
        $workDate = now()->subDays(5);

        /** @var User $user */
        $user = $this->users->first();

        // 勤怠を作成
        $attendance = $this->createAttendanceWithBreak(
            $user,
            $workDate,
        );

        // 修正データを作成
        $correction = $this->createCorrectionWithBreak(
            $attendance,
        );

        // 既存休憩・既存休憩修正を保持
        /** @var AttendanceBreak $existingBreak */
        $existingBreak = $attendance->attendanceBreaks->first();

        /** @var AttendanceCorrectionRequestBreak $existingBreakRequest */
        $existingBreakRequest = $correction->attendanceCorrectionRequestBreaks->first();

        // 新規休憩行を作成
        AttendanceCorrectionRequestBreak::factory()
            ->create([
                'attendance_correction_request_id' => $correction->id,
                'attendance_break_id' => null,
                'requested_break_start' => $workDate->copy()->setTime(17, 0, 0),
                'requested_break_end' => $workDate->copy()->setTime(17, 15, 0),
                'sort_order' => 2,
            ]);

        // 勤怠が1件、休憩が1件である事を確認
        $this->assertDatabaseCount('attendances', 1);
        $this->assertDatabaseCount('attendance_breaks', 1);

        // 修正データが承認待ちである事を確認
        $this->assertDatabaseHas('attendance_correction_requests', [
            'id' => $correction->id,
            'status' => 'pending',
        ]);

        // 管理者としてログインして、詳細画面を開く
        $this->actingAs($this->admin)
            ->get(route('admin.attendance.correction.detail', [
                'attendance_correct_request_id' => $correction->id
            ]))
            ->assertOk();

        // 承認ボタンを押す
        $this->actingAs($this->admin)
            ->put(route('admin.attendance.correction.approve', [
                'attendance_correct_request_id' => $correction->id
            ]))
            ->assertRedirect(route('admin.attendance.staff.monthly', [
                'id' => $correction->attendance->user->id,
                'month' => $correction->attendance->work_date->format('Y-m'),
            ]));

        // 勤怠データが1件のまま、休憩データが1件追加された事を確認
        $this->assertDatabaseCount('attendances', 1);
        $this->assertDatabaseCount('attendance_breaks', 2);

        // 勤怠データが更新された事を確認
        $attendance->refresh();
        $correction->refresh();

        $this->assertDatabaseHas('attendances', [
            'id' => $attendance->id,
            'clock_in' => $correction->requested_clock_in->format('Y-m-d H:i:s'),
            'clock_out' => $correction->requested_clock_out->format('Y-m-d H:i:s'),
        ]);

        // 休憩データが更新された事の確認
        $this->assertDatabaseHas('attendance_breaks', [
            'id' => $existingBreak->id,
            'break_start' => $existingBreakRequest->requested_break_start->format('Y-m-d H:i:s'),
            'break_end' => $existingBreakRequest->requested_break_end->format('Y-m-d H:i:s'),
            'sort_order' => 1,
        ]);

        // 新規休憩行も追加された事を確認
        $newBreakRequest = $correction
            ->attendanceCorrectionRequestBreaks
            ->where('sort_order', 2)
            ->first();

        $this->assertDatabaseHas('attendance_breaks', [
            'attendance_id' => $attendance->id,
            'break_start'   => $newBreakRequest->requested_break_start->format('Y-m-d H:i:s'),
            'break_end'     => $newBreakRequest->requested_break_end->format('Y-m-d H:i:s'),
            'sort_order'    => 2,
        ]);

        // 修正データが更新された事を確認
        $this->assertDatabaseHas('attendance_correction_requests', [
            'id' => $correction->id,
            'status' => 'approved',
            'reviewer_id' => $this->admin->id,
        ]);

        $this->assertDatabaseMissing('attendance_correction_requests', [
            'id' => $correction->id,
            'reviewed_at' => null,
        ]);

        // 申請一覧画面の承認済みに修正データが表示される事を確認
        $this->actingAs($this->admin)
            ->get(route('attendance.corrections.index', ['tab' => 'approved']))
            ->assertOk()
            ->assertSee(
                'data-testid="correction-row-' . $correction->id . '"',
                false
            );

        // 申請一覧画面の承認待ちに修正データが表示されない事を確認
        $this->actingAs($this->admin)
            ->get(route('attendance.corrections.index', ['tab' => 'pending']))
            ->assertOk()
            ->assertDontSee(
                'data-testid="correction-row-' . $correction->id . '"',
                false
            );

        // 一般ユーザーの申請一覧画面の承認済みに修正データが表示される事を確認
        $this->actingAs($user)
            ->get(route('attendance.corrections.index', ['tab' => 'approved']))
            ->assertOk()
            ->assertSee(
                'data-testid="correction-row-' . $correction->id . '"',
                false
            );

        // 一般ユーザーの申請一覧画面の承認待ちに修正データが表示されない事を確認
        $this->actingAs($user)
            ->get(route('attendance.corrections.index', ['tab' => 'pending']))
            ->assertOk()
            ->assertDontSee(
                'data-testid="correction-row-' . $correction->id . '"',
                false
            );
    }

    /**
     * 項目: 勤怠情報修正機能 (管理者)
     * 内容: (オプション) 再修正申請が承認待ちに正しく表示される
     */
    public function test_re_correction_requests_are_displayed_correctly_in_pending_tab(): void
    {
        // 勤怠用の日付リストを作成
        $start = now()->subMonthNoOverflow()->startOfMonth();
        $end = $start->copy()->addDays(13);
        $dates = $start->daysUntil($end);

        // 勤怠を作成
        $attendances = collect();

        foreach ($this->users as $user) {
            foreach ($dates as $date) {
                $attendance = $this->createAttendanceWithBreak(
                    $user,
                    $date,
                );

                $attendances->push($attendance);
            }
        }

        // 承認待ち・承認済みの修正データ作成用に分割
        [$pendingAttendances, $approvedAttendances] = $attendances->split(2);

        // 2回目承認待ち修正データ (1回目の承認履歴有)を作成
        $pendingCorrections = collect();
        $pendingCorrectionApprovedHistory = collect();

        foreach ($pendingAttendances as $attendance) {
            $firstCorrection = $this->createCorrectionWithBreak(
                $attendance,
                [
                    'status' => 'approved',
                    'reviewed_at' => now()->subDay(),
                    'reviewer_id' => $this->admin->id,
                ]
            );

            $pendingCorrectionApprovedHistory->push($firstCorrection);

            $secondCorrection = $this->createCorrectionWithBreak(
                $attendance,
            );

            $pendingCorrections->push($secondCorrection);
        }

        // 2回目承認済み修正データ (1回目の承認履歴有)を作成
        $approvedCorrections = collect();
        $approvedCorrectionApprovedHistory = collect();

        foreach ($approvedAttendances as $attendance) {
            $firstCorrection = $this->createCorrectionWithBreak(
                $attendance,
                [
                    'status' => 'approved',
                    'reviewed_at' => now()->subDay(),
                    'reviewer_id' => $this->admin->id,
                ]
            );

            $approvedCorrectionApprovedHistory->push($firstCorrection);

            $secondCorrection = $this->createCorrectionWithBreak(
                $attendance,
                [
                    'status' => 'approved',
                    'reviewed_at' => now(),
                    'reviewer_id' => $this->admin->id,
                ]
            );

            $approvedCorrections->push($secondCorrection);
        }

        // 管理者としてログインして、申請一覧画面の承認待ちタブを開く
        $response = $this->actingAs($this->admin)
            ->get(route('attendance.corrections.index', ['tab' => 'pending']));

        $response->assertOk();

        // 2回目承認待ちの場合、承認待ちが表示され、1回目承認済みが表示されない事を確認
        foreach ($pendingCorrections as $correction) {
            $response->assertSeeInOrder([
                'data-testid="correction-row-' . $correction->id . '"',
                $correction->statusLabel,
                $correction->attendance->user->name,
                $correction->attendance->work_date->format('Y/m/d'),
                $correction->request_remarks,
                $correction->created_at->format('Y/m/d'),
            ], false);
        }

        foreach ($pendingCorrectionApprovedHistory as $correction) {
            $response->assertDontSee(
                'data-testid="correction-row-' . $correction->id . '"',
                false
            );
        }

        // 2回目承認済みの場合、1,2回目承認済みが表示されない事を確認
        foreach ($approvedCorrections as $correction) {
            $response->assertDontSee(
                'data-testid="correction-row-' . $correction->id . '"',
                false
            );
        }

        foreach ($approvedCorrectionApprovedHistory as $correction) {
            $response->assertDontSee(
                'data-testid="correction-row-' . $correction->id . '"',
                false
            );
        }
    }

    /**
     * 項目: 勤怠情報修正機能 (管理者)
     * 内容: (オプション) 再修正申請が承認済みに正しく表示される
     */
    public function test_re_correction_requests_are_displayed_correctly_in_approved_tab(): void
    {
        // 勤怠用の日付リストを作成
        $start = now()->subMonthNoOverflow()->startOfMonth();
        $end = $start->copy()->addDays(13);
        $dates = $start->daysUntil($end);

        // 勤怠を作成
        $attendances = collect();

        foreach ($this->users as $user) {
            foreach ($dates as $date) {
                $attendance = $this->createAttendanceWithBreak(
                    $user,
                    $date,
                );

                $attendances->push($attendance);
            }
        }

        // 承認待ち・承認済みの修正データ作成用に分割
        [$pendingAttendances, $approvedAttendances] = $attendances->split(2);

        // 2回目承認待ち修正データ (1回目の承認履歴有)を作成
        $pendingCorrections = collect();
        $pendingCorrectionApprovedHistory = collect();

        foreach ($pendingAttendances as $attendance) {
            $firstCorrection = $this->createCorrectionWithBreak(
                $attendance,
                [
                    'status' => 'approved',
                    'reviewed_at' => now()->subDay(),
                    'reviewer_id' => $this->admin->id,
                ]
            );

            $pendingCorrectionApprovedHistory->push($firstCorrection);

            $secondCorrection = $this->createCorrectionWithBreak(
                $attendance,
            );

            $pendingCorrections->push($secondCorrection);
        }

        // 2回目承認済み修正データ (1回目の承認履歴有)を作成
        $approvedCorrections = collect();
        $approvedCorrectionApprovedHistory = collect();

        foreach ($approvedAttendances as $attendance) {
            $firstCorrection = $this->createCorrectionWithBreak(
                $attendance,
                [
                    'status' => 'approved',
                    'reviewed_at' => now()->subDay(),
                    'reviewer_id' => $this->admin->id,
                ]
            );

            $approvedCorrectionApprovedHistory->push($firstCorrection);

            $secondCorrection = $this->createCorrectionWithBreak(
                $attendance,
                [
                    'status' => 'approved',
                    'reviewed_at' => now(),
                    'reviewer_id' => $this->admin->id,
                ]
            );

            $approvedCorrections->push($secondCorrection);
        }
        // 管理者としてログインして、申請一覧画面の承認済みタブを開く
        $response = $this->actingAs($this->admin)
            ->get(route('attendance.corrections.index', ['tab' => 'approved']));

        $response->assertOk();

        // 2回目承認待ちの場合、1回目の承認済みが表示され、承認待ちが表示されない事を確認
        foreach ($pendingCorrectionApprovedHistory as $correction) {
            $response->assertSeeInOrder([
                'data-testid="correction-row-' . $correction->id . '"',
                $correction->statusLabel,
                $correction->attendance->user->name,
                $correction->attendance->work_date->format('Y/m/d'),
                $correction->request_remarks,
                $correction->created_at->format('Y/m/d'),
            ], false);
        }

        foreach ($pendingCorrections as $correction) {
            $response->assertDontSee(
                'data-testid="correction-row-' . $correction->id . '"',
                false
            );
        }

        // 2回目承認済みの場合、1,2回目承認済みが表示される事を確認
        foreach ($approvedCorrections as $correction) {
            $response->assertSeeInOrder([
                'data-testid="correction-row-' . $correction->id . '"',
                $correction->statusLabel,
                $correction->attendance->user->name,
                $correction->attendance->work_date->format('Y/m/d'),
                $correction->request_remarks,
                $correction->created_at->format('Y/m/d'),
            ], false);
        }

        foreach ($approvedCorrectionApprovedHistory as $correction) {
            $response->assertSeeInOrder([
                'data-testid="correction-row-' . $correction->id . '"',
                $correction->statusLabel,
                $correction->attendance->user->name,
                $correction->attendance->work_date->format('Y/m/d'),
                $correction->request_remarks,
                $correction->created_at->format('Y/m/d'),
            ], false);
        }
    }
}
