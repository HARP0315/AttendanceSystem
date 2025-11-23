<?php

namespace Database\Factories;
use App\Models\AttendanceCorrection;
use App\Models\CorrectionRequest;

use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\AttendanceCorrection>
 */
class AttendanceCorrectionFactory extends Factory
{
    protected $model = AttendanceCorrection::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {

        $start = $this->faker->time('H:i:s', '11:00:00');
        $end = date('H:i:s', '19:00:00');

        return [
            'correction_request_id' => CorrectionRequest::factory(),
            'work_start_time' => $start,
            'work_end_time' => $end,
        ];
    }
}
