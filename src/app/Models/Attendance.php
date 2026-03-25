<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Attendance extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'work_date',
        'clock_in',
        'clock_out',
    ];

    protected $casts = [
        'work_date' => 'date',
        'clock_in' => 'datetime',
        'clock_out' => 'datetime',
    ];

    // usersテーブルとのリレーション
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    // attendance_breaksテーブルとのリレーション
    public function attendanceBreaks()
    {
        return $this->hasMany(AttendanceBreak::class)->orderBy('sort_order');
    }

    // attendance_correction_requestsテーブルとのリレーション
    public function attendanceCorrectionRequests()
    {
        return $this->hasMany(AttendanceCorrectionRequest::class);
    }

    // 勤怠ステータスを計算
    public function getStatusAttribute()
    {
        // 各時間の抽出
        $clockIn = $this->clock_in;
        $clockOut = $this->clock_out;
        $latestBreak = $this->attendanceBreaks->sortByDesc('sort_order')->first();

        // 出勤前
        if (!$clockIn) {
            return '勤務外';
        }

        // 退勤済
        if ($clockOut) {
            return '退勤済';
        }

        // 休憩中
        if ($latestBreak && !$latestBreak->break_end) {
            return '休憩中';
        }

        return '出勤中';
    }

    // 最新の休憩レコードを取得
    public function getLatestBreakAttribute()
    {
        return $this->attendanceBreaks->sortByDesc('sort_order')->first();
    }

    // 休憩時間の合計を計算
    public function getBreakTotalAttribute()
    {
        return $this->attendanceBreaks->sum('duration');
    }

    // 勤怠時間の合計を計算
    public function getWorkTotalAttribute()
    {
        $clockIn = $this->clock_in;
        $clockOut = $this->clock_out;

        if (! $clockIn || ! $clockOut) {
            return 0;
        }

        $workDuration = $clockIn->diffInSeconds($clockOut);
        $breakTotal = $this->breakTotal;

        return $workDuration - $breakTotal;
    }

    // 合計休憩時間の表示形式を指定
    public function getBreakTotalFormattedAttribute()
    {
        if (!$this->breakTotal) {
            return '';
        }

        $hours = intdiv($this->breakTotal, 3600);
        $minutes = intdiv($this->breakTotal % 3600, 60);

        return sprintf('%d:%02d', $hours, $minutes);
    }

    // 合計勤務時間の表示形式を指定
    public function getWorkTotalFormattedAttribute()
    {
        if (!$this->workTotal) {
            return '';
        }

        $hours = intdiv($this->workTotal, 3600);
        $minutes = intdiv($this->workTotal % 3600, 60);

        return sprintf('%d:%02d', $hours, $minutes);
    }
}
