<?php

namespace Database\Factories;

use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Carbon;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Attendance>
 */
class AttendanceFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        // 勤務日を定義
        $workDate = Carbon::instance(
            fake()->dateTimeBetween('-30 days', 'today')
        )->startOfDay();

        // 出勤を定義
        $clockIn = $workDate->copy()->setTime(
            fake()->numberBetween(8, 10),
            fake()->numberBetween(0, 59)
        );

        // 退勤時間を定義
        $clockOut = $clockIn->copy()->addHours(fake()->numberBetween(8, 10));

        return [
            'user_id' => User::factory(),
            'work_date' => $workDate->toDateString(),
            'clock_in' => $clockIn,
            'clock_out' => $clockOut,
        ];
    }
}
