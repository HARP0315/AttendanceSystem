<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\CorrectionRequest;
use App\Models\BreakTimeCorrection;



class BreakTimeCorrectionTableSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {

        $correctionRequest1 = CorrectionRequest::with('targetAttendance.breakRecords')
                                                ->find(1);
        $correctionRequest2 = CorrectionRequest::find(2);
        $correctionRequest3 = CorrectionRequest::find(3);

        $breakTime = $correctionRequest1->targetAttendance->breakRecords->first();

        BreakTimeCorrection::create([
            'correction_request_id' => $correctionRequest1->id,
            'break_start_time' => $breakTime->break_start_time,
            'break_end_time' => $breakTime->break_end_time,
        ]);

        BreakTimeCorrection::create([
            'correction_request_id' => $correctionRequest2->id,
            'break_start_time' => '11:30',
            'break_end_time' => '12:30',
        ]);

        BreakTimeCorrection::create([
            'correction_request_id' => $correctionRequest3->id,
            'break_start_time' => '12:00',
            'break_end_time' => '13:00',
        ]);
    }
}
