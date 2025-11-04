<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class AttendanceCorrection extends Model
{
    use HasFactory;

    protected $fillable = [
        'correction_request_id',
        'work_start_time',
        'work_end_time',
    ];

    public function targetRequestForAttendance()
    {
        return $this->belongsTo('App\Models\CorrectionRequest', 'correction_request_id');
    }
}
