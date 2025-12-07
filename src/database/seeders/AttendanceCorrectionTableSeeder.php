<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\CorrectionRequest;
use App\Models\AttendanceCorrection;

class AttendanceCorrectionTableSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {

        $correctionRequest1 = CorrectionRequest::with('targetAttendance')
                                                ->find(1);
        $correctionRequest2 = CorrectionRequest::with('targetAttendance')
                                                ->find(2);
        $correctionRequest3 = CorrectionRequest::find(3);

        AttendanceCorrection::create([

            'correction_request_id' => $correctionRequest1->id,
            'work_start_time' => $correctionRequest1->targetAttendance->work_start_time,
            'work_end_time' => '19:00'

        ]);

        AttendanceCorrection::create([

            'correction_request_id' => $correctionRequest2->id,
            'work_start_time' => $correctionRequest2->targetAttendance->work_start_time,
            'work_end_time' => $correctionRequest2->targetAttendance->work_end_time,

        ]);

        AttendanceCorrection::create([

            'correction_request_id' => $correctionRequest3->id,
            'work_start_time' => '9:00',
            'work_end_time' => '18:00',

        ]);
    }
}
