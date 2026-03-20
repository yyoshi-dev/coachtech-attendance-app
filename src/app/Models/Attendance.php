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

    // usersテーブルとのリレーション
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    // attendance_breaksテーブルとのリレーション
    public function attendanceBreaks()
    {
        return $this->hasMany(AttendanceBreak::class);
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
}
