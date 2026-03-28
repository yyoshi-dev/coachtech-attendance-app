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

    protected $casts = [
        'requested_clock_in' => 'datetime',
        'requested_clock_out' => 'datetime',
        'reviewed_at' => 'datetime',
    ];

    // attendancesテーブルとのリレーション
    public function attendance()
    {
        return $this->belongsTo(Attendance::class);
    }

    // attendance_correction_request_breaksテーブルとのリレーション
    public function attendanceCorrectionRequestBreaks()
    {
        return $this->hasMany(AttendanceCorrectionRequestBreak::class)
            ->orderBy('sort_order');
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

    // ステータス表示用ラベル
    public function getStatusLabelAttribute()
    {
        return match ($this->status) {
            'pending' => '承認待ち',
            'approved' => '承認済み',
            default => '不明',
        };
    }
}
