<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use App\Models\BreakTime;
use App\Models\Attendance;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\BreakTime>
 */
class BreakTimeFactory extends Factory
{
    protected $model = BreakTime::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $startTime = $this->faker->time('H:i:s', '13:00:00');
        $endTime = $this->faker->time('H:i:s', '14:00:00');

        return [
            'attendance_id' => Attendance::factory(),
            'break_start_time' => $startTime,
            'break_end_time' => $endTime,
        ];
    }
}
