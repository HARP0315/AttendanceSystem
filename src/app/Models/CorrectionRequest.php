<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class CorrectionRequest extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'attendance_id',
        'work_date',
        'reason',
        'request_status'
    ];

    public function attendanceCorrection()
    {
        return $this->hasOne('App\Models\AttendanceCorrection');
    }

    public function targetUser()
    {
        return $this->belongsTo('App\Models\User', 'user_id');
    }

    public function targetAttendance()
    {
        return $this->belongsTo('App\Models\Attendance', 'attendance_id');
    }

    public function targetBreakTime()
    {
        return $this->belongsTo('App\Models\BreakTime', 'break_time_id');
    }
}
