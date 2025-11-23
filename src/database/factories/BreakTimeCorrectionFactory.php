<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use App\Models\BreakTimeCorrection;
use App\Models\CorrectionRequest;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\BreakTimeCorrection>
 */
class BreakTimeCorrectionFactory extends Factory
{

    protected $model = BreakTimeCorrection::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {

        $start = $this->faker->time('H:i:s', '13:00:00');
        $end = date('H:i:s', strtotime($start) + 30 * 60); // +30分

        return [
            'correction_request_id' => CorrectionRequest::factory(),
            'break_start_time' => $start,
            'break_end_time' => $end,
        ];
    }
}
