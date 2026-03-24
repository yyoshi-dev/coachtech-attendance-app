<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class AttendanceBreak extends Model
{
    use HasFactory;

    protected $fillable = [
        'attendance_id',
        'break_start',
        'break_end',
        'sort_order',
    ];

    protected $casts = [
        'break_start' => 'datetime',
        'break_end' => 'datetime',
    ];

    // attendancesテーブルとのリレーション
    public function attendance()
    {
        return $this->belongsTo(Attendance::class);
    }

    // attendance_correction_request_breaksテーブルとのリレーション
    public function attendanceCorrectionRequestBreaks()
    {
        return $this->hasMany(AttendanceCorrectionRequestBreak::class);
    }

    // 休憩時間を計算
    public function getDurationAttribute()
    {
        $breakStart = $this->break_start;
        $breakEnd = $this->break_end;

        if (! $breakStart || ! $breakEnd) {
            return 0;
        }

        return $breakStart->diffInSeconds($breakEnd);
    }
}
