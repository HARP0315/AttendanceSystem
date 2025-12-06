<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\Attendance;
use App\Models\BreakTime;

class BreakTimeTableSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $attendance1 = Attendance::find(1);
        $attendance2 = Attendance::find(2);

        BreakTime::create([
            'attendance_id' => $attendance1->id,
            'break_start_time' => '12:00',
            'break_end_time' => '13:00',
        ]);

        BreakTime::create([
            'attendance_id' => $attendance2->id,
            'break_start_time' => '13:00',
            'break_end_time' => '14:00',
        ]);
    }
}
