<?php

namespace Database\Factories;

use App\Models\Attendance;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\AttendanceBreak>
 */
class AttendanceBreakFactory extends Factory
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
            'break_start' => now(),
            'break_end' => now()->addHour(),
            'sort_order' => 1,
        ];
    }

    public function forAttendance(Attendance $attendance, int $sortOrder = 1): static
    {
        // 出退勤時間
        $clockIn = Carbon::parse($attendance->clock_in);
        $clockOut = Carbon::parse($attendance->clock_out);

        // 休憩開始・終了時間を定義
        $breakStart = $clockIn->copy()->addHours(fake()->numberBetween(2, 4));
        $breakEnd = $breakStart->copy()->addMinutes(fake()->numberBetween(30, 60));

        // 休憩終了時間が退勤時間を超えないように調整
        if ($breakEnd->gt($clockOut)) {
            $breakEnd = $clockOut->copy()->subMinutes(1);
        }

        return $this->for($attendance)->state([
            'attendance_id' => $attendance->id,
            'break_start' => $breakStart,
            'break_end' => $breakEnd,
            'sort_order' => $sortOrder,
        ]);
    }
}
