<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class BreakTimeCorrection extends Model
{
    use HasFactory;

    protected $fillable = [
        'correction_request_id',
        'break_start_time',
        'break_end_time'
    ];

    public function targetRequestForBreak()
    {
        return $this->belongsTo('App\Models\CorrectionRequest', 'correction_request_id');
    }

}
