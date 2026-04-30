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

        return [
            'user_id' => User::factory(),
            'remarks' => null,
            ...$this->buildAttendanceTimes($workDate),
        ];
    }

    /**
     * 指定した勤務日で勤怠を生成
     */
    public function forWorkDate(Carbon|string $date): static
    {
        // 日付をCarbonに変換
        $workDate = $date instanceof Carbon
            ? $date->copy()->startOfDay()
            : Carbon::parse($date)->startOfDay();

        return $this->state(fn () => [
            'remarks' => null,
            ...$this->buildAttendanceTimes($workDate),
        ]);
    }

    /**
     * 出退勤時間設定用の共通関数
     */
    private function buildAttendanceTimes(Carbon $workDate): array
    {
        // 出勤時間を定義
        $clockIn = $workDate->copy()->setTime(
            fake()->numberBetween(8, 9),
            fake()->numberBetween(0, 59)
        );

        // 退勤時間を定義
        $clockOut = $clockIn->copy()->addHours(fake()->numberBetween(8, 10));

        return [
            'work_date' => $workDate->toDateString(),
            'clock_in' => $clockIn,
            'clock_out' => $clockOut,
        ];
    }
}
