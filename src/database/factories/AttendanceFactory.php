<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use App\Models\Attendance;
use App\Models\User;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Attendance>
 */
class AttendanceFactory extends Factory
{

    protected $model = Attendance::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $startTime = $this->faker->time('H:i:s', '10:00:00');
        $endTime = $this->faker->time('H:i:s', '18:00:00');

        return [
            'user_id' => User::factory(),
            'work_date' => $this->faker->date(),
            'work_start_time' => $startTime,
            'work_end_time' => $endTime,
            'reason' => $this->faker->text(15),
            'status' => $this->faker->numberBetween(1, 3),
            'is_deleted' => $this->faker->numberBetween(0, 1),
        ];
    }
}
