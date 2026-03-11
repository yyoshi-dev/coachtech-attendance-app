<?php

namespace Database\Factories;

use App\Models\Attendance;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\AttendanceCorrectionRequest>
 */
class AttendanceCorrectionRequestFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'attendance_id' => Attendance::factory(),
            'request_user_id' => User::factory(),
            'requested_clock_in' => now(),
            'requested_clock_out' => now()->addHour(),
            'request_remarks' => fake()->text(20),
            'status' => 'pending',
            'reviewed_at' => null,
            'reviewer_id' => null,
        ];
    }

    public function forAttendance(Attendance $attendance): static
    {
        // 出退勤時間
        $clockIn = Carbon::parse($attendance->clock_in);
        $clockOut = Carbon::parse($attendance->clock_out);

        // 出退勤時間を修正
        $requestedClockIn = $clockIn->copy()->addMinutes(fake()->numberBetween(5, 30));
        $requestedClockOut = $clockOut->copy()->subMinutes(fake()->numberBetween(5, 30));

        return $this->for($attendance)->state([
            'request_user_id' => $attendance->user_id,
            'requested_clock_in' => $requestedClockIn,
            'requested_clock_out' => $requestedClockOut,
        ]);
    }
}
