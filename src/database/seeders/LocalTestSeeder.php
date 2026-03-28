<?php

namespace Database\Seeders;

use App\Models\Attendance;
use App\Models\AttendanceBreak;
use App\Models\AttendanceCorrectionRequest;
use App\Models\AttendanceCorrectionRequestBreak;
use App\Models\User;
use Illuminate\Support\Carbon;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class LocalTestSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        /*
        * Usersの作成
        */
        $users = collect();

        // 検証用一般ユーザーの作成
        $testUser = User::factory()->create([
            'name' => 'Test User',
            'email' => 'test_user@example.com',
            'password' => Hash::make('test1234'),
        ]);
        $users->push($testUser);

        // 検証用管理者の作成
        $admin = User::factory()->admin()->create([
            'name' => 'Test Admin',
            'email' => 'test_admin@example.com',
            'password' => Hash::make('test1234'),
        ]);

        // その他一般ユーザーのダミーデータの作成
        $users = $users->merge(
            User::factory()->count(5)->create()
        );

        /*
        * 出退勤・休憩のダミーデータの作成
        */
        // 日付リストを作成
        $today = Carbon::today();
        $start = $today->copy()->subMonthNoOverflow(2)->startOfMonth();
        $dates = $start->daysUntil($today->copy()->subDay())->toArray();
        $dates = array_filter(
            $dates, fn($d) => !in_array($d->dayOfWeek, [Carbon::SATURDAY, Carbon::SUNDAY])
        );

        // 修正申請作成に使う参照を保持
        $attendanceMap = [];
        $firstBreakMap = [];

        // ユーザー毎に出退勤・休憩のダミーデータを生成
        foreach ($users as $user) {
            $userAttendances = collect();

            foreach($dates as $date) {
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
        // Test Userの勤怠を取得
        $testAttendances = $attendanceMap[$testUser->id];

        $pendingTestAttendances = $testAttendances->take(10);
        $approvedTestAttendances = $testAttendances->slice(10, 10);

        // Test Userの承認待ちデータを作成
        foreach ($pendingTestAttendances as $attendance) {
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
        foreach ($approvedTestAttendances as $attendance) {
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

        // その他ユーザーの承認待ちデータを作成
        foreach ($users->where('id', '!=', $testUser->id) as $user) {
            $attendance = $attendanceMap[$user->id]->first();

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
    }
}
