<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class BreakTime extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'attendance_id',
        'break_start_time',
        'break_end_time'
    ];

    public function breakUser()
    {
        return $this->belongsTo('App\Models\User', 'user_id');
    }

    public function attendance()
    {
        return $this->belongsTo('App\Models\Attendance', 'attendance_id');
    }

}
