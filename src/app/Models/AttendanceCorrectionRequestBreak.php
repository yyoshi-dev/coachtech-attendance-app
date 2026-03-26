<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class AttendanceCorrectionRequestBreak extends Model
{
    use HasFactory;

    protected $fillable = [
        'correction_request_id',
        'attendance_break_id',
        'requested_break_start',
        'requested_break_end',
        'sort_order',
    ];

    protected $casts = [
        'requested_break_start' => 'datetime',
        'requested_break_end' => 'datetime',
    ];

    // attendance_correction_requestsテーブルとのリレーション
    public function attendanceCorrectionRequest()
    {
        return $this->belongsTo(AttendanceCorrectionRequest::class);
    }

    // attendance_breaksテーブルとのリレーション
    public function attendanceBreak()
    {
        return $this->belongsTo(AttendanceBreak::class);
    }
}
