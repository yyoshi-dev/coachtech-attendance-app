<?php

namespace App\Models;

use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class User extends Authenticatable implements MustVerifyEmail
{
    /** @use HasFactory<\Database\Factories\UserFactory> */
    use HasFactory, Notifiable;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'email',
        'password',
        'role',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var list<string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
        ];
    }

    // attendancesテーブルとのリレーション
    public function attendances()
    {
        return $this->hasMany(Attendance::class);
    }

    // attendance_correction_requestsテーブル (request_user_id)とのリレーション
    public function requestedCorrections()
    {
        return $this->hasMany(AttendanceCorrectionRequest::class, 'request_user_id');
    }

    // attendance_correction_requestsテーブル (reviewer_id)とのリレーション
    public function reviewedCorrections()
    {
        return $this->hasMany(AttendanceCorrectionRequest::class, 'reviewer_id');
    }
}
