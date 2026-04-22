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
    ): Attendance
    {
        $attendance = Attendance::factory()
            ->for($user)
            ->forWorkDate($workDate)
            ->create();

        AttendanceBreak::factory()
            ->forAttendance($attendance)
            ->create();

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
        $start = now()->subMonthNoOverflow(1)->startOfMonth();
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
        $start = now()->subMonthNoOverflow(1)->startOfMonth();
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
        $user = $this->users->first();

        // 勤怠を作成
        $attendance = $this->createAttendanceWithBreak(
            $user,
            $workDate,
        );

        // 修正データの作成
        $correction = $this->createCorrectionWithBreak(
            $attendance,
        );

        // 管理者としてログインして、詳細画面を開く
        $response = $this->actingAs($this->admin)
            ->get(route('admin.attendance.correction.detail', [
                'attendance_correct_request_id' => $correction->id
            ]));

        $response->assertOk();

        // 詳細画面の内容が選択した情報と一致する事を確認
        // 名前の確認
        $response->assertSeeInOrder([
            'data-testid="user-name"',
            $user->name,
        ], false);

        // 日付の確認
        $response->assertSeeInOrder([
            'data-testid="work-date-year"',
            $workDate->format('Y') . '年',
        ], false);

        $response->assertSeeInOrder([
            'data-testid="work-date-month-day"',
            $workDate->format('n月j日'),
        ], false);

        // 出勤・退勤の確認
        $response->assertSeeInOrder([
            'data-testid="requested-clock-in-text"',
            $correction->requested_clock_in->format('H:i'),
        ], false);

        $response->assertSeeInOrder([
            'data-testid="requested-clock-out-text"',
            $correction->requested_clock_out->format('H:i'),
        ], false);

        // 休憩の確認
        foreach ($correction->attendanceCorrectionRequestBreaks as $index => $break) {
            $response->assertSeeInOrder([
                'data-testid="requested-break-start-text-' . $index . '"',
                $break->requested_break_start->format('H:i'),
            ], false);

            $response->assertSeeInOrder([
                'data-testid="requested-break-end-text-' . $index . '"',
                $break->requested_break_end->format('H:i'),
            ], false);
        }

        // 備考の確認
        $response->assertSeeInOrder([
            'data-testid="request-remarks-text"',
            $correction->request_remarks,
        ], false);

        // 承認ボタンが表示され、承認済みボタンが表示されない事を確認
        $response->assertSeeText('承認');
        $response->assertDontSeeText('承認済み');
    }

    /**
     * 項目: 勤怠情報修正機能 (管理者)
     * 内容: 修正申請の承認処理が正しく行われる
     */
    public function test_admin_can_approve_user_attendance_correction_request(): void
    {
        $workDate = now()->subDays(5);
        $user = $this->users->first();

        // 勤怠を作成
        $attendance = $this->createAttendanceWithBreak(
            $user,
            $workDate,
        );

        // 修正データの作成
        $correction = $this->createCorrectionWithBreak(
            $attendance,
        );

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

        // 勤怠データが更新された事を確認
        $attendance->refresh();
        $correction->refresh();

        $this->assertDatabaseHas('attendances', [
            'id' => $attendance->id,
            'clock_in' => $correction->requested_clock_in->format('Y-m-d H:i:s'),
            'clock_out' => $correction->requested_clock_out->format('Y-m-d H:i:s'),
        ]);

        $this->assertDatabaseHas('attendance_breaks', [
            'id' => $attendance->attendanceBreaks->first()->id,
            'break_start' => $correction
                ->attendanceCorrectionRequestBreaks
                ->first()
                ->requested_break_start
                ->format('Y-m-d H:i:s'),
            'break_end' => $correction
                ->attendanceCorrectionRequestBreaks
                ->first()
                ->requested_break_end
                ->format('Y-m-d H:i:s'),
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
    }
}
