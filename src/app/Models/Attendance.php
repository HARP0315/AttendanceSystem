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
        'work_start_time',
        'work_end_time',
        'reason',
        'status',
        'is_deleted',
    ];

    public function breakRecords()
    {
        return $this->hasMany('App\Models\BreakTime');
    }

    public function correctionRequests()
    {
        return $this->hasMany('App\Models\CorrectionRequest');
    }

    public function user()
    {
        return $this->belongsTo('App\Models\User', 'user_id');
    }
}
