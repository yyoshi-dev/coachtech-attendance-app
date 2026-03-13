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
}
