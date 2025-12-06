<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\User;
use App\Models\Attendance;


class AttendanceTableSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $user1 = User::find(2);
        $user2 = User::find(3);
        $user3 = User::find(4);

        Attendance::create([
            'user_id' => $user1->id,
            'work_date' => '2025-01-01',
            'work_start_time' => '09:00',
            'work_end_time' => '18:00',
            'reason' => null,
            'status' => '3',
            'is_deleted' => '0',
        ]);

        Attendance::create([
            'user_id' => $user2->id,
            'work_date' => '2025-01-01',
            'work_start_time' => '10:00',
            'work_end_time' => null,
            'reason' => null,
            'status' => '1',
            'is_deleted' => '0',
            ]);

        Attendance::create([
            'user_id' => $user3->id,
            'work_date' => '2025-01-01',
            'work_start_time' => '08:30',
            'work_end_time' => '18:30',
            'reason' => null,
            'status' => '3',
            'is_deleted' => '0',
        ]);
    }
}
