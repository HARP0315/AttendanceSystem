<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\User;
use App\Models\Attendance;
use App\Models\CorrectionRequest;

class CorrectionRequestTableSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $user = User::find(5);
        $attendance1 = Attendance::where('user_id',3)
                                 ->where('id', 2)
                                 ->first();
        $attendance2 = Attendance::where('user_id',4)
                                 ->where('id', 3)
                                 ->first();

        CorrectionRequest::create([

            'user_id' => $attendance1->user_id,
            'attendance_id' => $attendance1->id,
            'work_date' => $attendance1->work_date,
            'reason' => '退勤押し忘れました',
            'request_status' => 1,

        ]);

        CorrectionRequest::create([

            'user_id' => $attendance2->user_id,
            'attendance_id' => $attendance2->id,
            'work_date' => $attendance2->work_date,
            'reason' => '休憩入れ忘れていました',
            'request_status' => 1,

        ]);

        CorrectionRequest::create([

            'user_id' => $user->id,
            'attendance_id' => null,
            'work_date' => '2025-01-01',
            'reason' => '勤怠入れ忘れていました',
            'request_status' => 1,

        ]);
    }
}
