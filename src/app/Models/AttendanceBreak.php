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
}
