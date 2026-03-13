<?php

namespace Database\Factories;

use App\Models\AttendanceBreak;
use App\Models\AttendanceCorrectionRequest;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\AttendanceCorrectionRequestBreak>
 */
class AttendanceCorrectionRequestBreakFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'correction_request_id' => AttendanceCorrectionRequest::factory(),
            'attendance_break_id' => null,
            'requested_break_start' => now(),
            'requested_break_end' => now()->addHour(),
            'sort_order' => 1
        ];
    }

    public function forAttendanceCorrectionRequest(
        AttendanceCorrectionRequest $request,
        AttendanceBreak $break,
        int $sortOrder = 1
    ): static {
        // 休憩開始・終了時間
        $breakStart = Carbon::parse($break->break_start);
        $breakEnd = Carbon::parse($break->break_end);

        // 休憩開始・終了時間を修正
        $requestedBreakStart = $breakStart->copy()->addMinutes(fake()->numberBetween(-10, 10));
        $requestedBreakEnd = $breakEnd->copy()->addMinutes(fake()->numberBetween(-10, 10));

        // 退勤時間を超えないように調整
        $requestedClockOut = Carbon::parse($request->requested_clock_out);
        if ($requestedBreakEnd->gt($requestedClockOut)) {
            $requestedBreakEnd = $requestedClockOut->copy()->subMinutes(1);
        }

        return $this->state([
            'correction_request_id' => $request->id,
            'attendance_break_id' => $break->id,
            'requested_break_start' =>  $requestedBreakStart,
            'requested_break_end' =>$requestedBreakEnd ,
            'sort_order' => $sortOrder,
        ]);
    }
}
