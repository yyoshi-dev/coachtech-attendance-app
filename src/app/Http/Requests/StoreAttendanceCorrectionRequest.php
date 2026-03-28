<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreAttendanceCorrectionRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'requested_clock_in' => ['before_or_equal:requested_clock_out'],
            'requested_clock_out' => ['after_or_equal:requested_clock_in'],
            'requested_break_start.*' => ['after_or_equal:requested_clock_in', 'before_or_equal:requested_clock_out'],
            'requested_break_end.*' => ['before_or_equal:requested_clock_out'],
            'request_remarks' => ['required'],
        ];
    }

    public function messages()
    {
        return [
            'requested_clock_in.before_or_equal' => '出勤時間もしくは退勤時間が不適切な値です',
            'requested_clock_out.after_or_equal' => '出勤時間もしくは退勤時間が不適切な値です',
            'requested_break_start.*.after_or_equal' => '休憩時間が不適切な値です',
            'requested_break_start.*.before_or_equal' => '休憩時間が不適切な値です',
            'requested_break_end.*.before_or_equal' => '休憩時間もしくは退勤時間が不適切な値です',
            'request_remarks.required' => '備考を記入してください',
        ];
    }

    /**
     * バリデーション前のデータ整形
     */
    protected function prepareForValidation(): void
    {
        // リクエストから配列を取得 (空の場合は空配列)
        $starts = $this->input('requested_break_start', []);
        $ends = $this->input('requested_break_end', []);
        $ids = $this->input('attendance_break_id', []);

        $filteredStarts = [];
        $filteredEnds = [];
        $filteredIds = [];

        foreach ($starts as $index => $start) {
            $end = $ends[$index] ?? null;
            $id = $ids[$index] ?? null;

            // 新規休憩行かつ開始・終了が空の場合その行を無視
            if (!$id && blank($start) && blank($end)) {
                continue;
            }

            $filteredStarts[$index] = $start;
            $filteredEnds[$index] = $end;
            $filteredIds[$index] = $id;
        }

        // 加工した配列をリクエストにマージ
        $this->merge([
            'requested_break_start' => $filteredStarts,
            'requested_break_end' => $filteredEnds,
            'attendance_break_id' => $filteredIds,
        ]);
    }
}
