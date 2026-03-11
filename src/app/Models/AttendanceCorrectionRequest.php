<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class AttendanceCorrectionRequest extends Model
{
    use HasFactory;

    protected $fillable = [
        'attendance_id',
        'request_user_id',
        'requested_clock_in',
        'requested_clock_out',
        'request_remarks',
        'status',
        'reviewed_at',
        'reviewer_id',
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

    // usersテーブル (request_user_id)とのリレーション
    public function requestUser()
    {
        return $this->belongsTo(User::class, 'request_user_id');
    }

    // usersテーブル (reviewer_id)とのリレーション
    public function reviewer()
    {
        return $this->belongsTo(User::class, 'reviewer_id');
    }
}
