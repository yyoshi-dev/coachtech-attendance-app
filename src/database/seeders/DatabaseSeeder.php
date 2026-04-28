<?php

namespace Database\Seeders;

use App\Models\Attendance;
use App\Models\AttendanceBreak;
use App\Models\AttendanceCorrectionRequest;
use App\Models\AttendanceCorrectionRequestBreak;
use App\Models\User;
use Illuminate\Support\Carbon;
use Illuminate\Database\Seeder;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        /*
        * Usersの作成
        */
        // 管理者の作成
        $admin = User::factory()->admin()->create();

        // 一般ユーザーのダミーデータの作成
        $users = User::factory()->count(5)->create();

        /*
        * 出退勤・休憩のダミーデータの作成
        */
        // 日付リストを作成
        $today = Carbon::today();
        $start = $today->copy()->subMonthNoOverflow(2)->startOfMonth();
        $dates = $start->daysUntil($today)->toArray();
        $dates = array_filter(
            $dates, fn($d) => !in_array($d->dayOfWeek, [Carbon::SATURDAY, Carbon::SUNDAY])
        );

        // 修正申請作成に使う参照を保持
        $attendanceMap = [];
        $firstBreakMap = [];

        // ユーザー毎に出退勤・休憩のダミーデータを生成
        foreach ($users as $user) {
            $userAttendances = collect();

            foreach ($dates as $date) {
                // 出退勤時間
                $attendance = Attendance::factory()
                    ->recycle($user)
                    ->forWorkDate($date)
                    ->create();

                // 1回目の休憩
                $firstBreak = AttendanceBreak::factory()
                    ->forAttendance($attendance, 1)
                    ->create();

                // 2回目の休憩
                AttendanceBreak::factory()
                    ->afterBreak($attendance, $firstBreak->break_end, 2)
                    ->create();

                $userAttendances->push($attendance);
                $firstBreakMap[$attendance->id] = $firstBreak;
            }

            $attendanceMap[$user->id] = $userAttendances->sortByDesc('work_date')->values();
        }

        /*
        * 修正申請のダミーデータの作成
        */
        foreach ($users as $user) {
            $attendances = $attendanceMap[$user->id];

            $pendingAttendances = $attendances->take(10);
            $approvedAttendances = $attendances->slice(10, 10);

            foreach ($pendingAttendances as $attendance) {
                $correction = AttendanceCorrectionRequest::factory()
                    ->forAttendance($attendance)
                    ->create();

                AttendanceCorrectionRequestBreak::factory()
                    ->forAttendanceCorrectionRequest(
                        $correction,
                        $firstBreakMap[$attendance->id],
                        1,
                    )
                    ->create();
            }

            // Test Userの承認済みデータを作成
            foreach ($approvedAttendances as $attendance) {
                $correction = AttendanceCorrectionRequest::factory()
                    ->forAttendance($attendance)
                    ->create([
                        'status' => 'approved',
                        'reviewed_at' => Carbon::parse($attendance->work_date)
                            ->copy()
                            ->addDay()
                            ->setTime(17, 0),
                        'reviewer_id' => $admin->id,
                    ]);

                AttendanceCorrectionRequestBreak::factory()
                    ->forAttendanceCorrectionRequest(
                        $correction,
                        $firstBreakMap[$attendance->id],
                        1,
                    )
                    ->create();
            }
        }
    }
}
