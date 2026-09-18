<?php

namespace Database\Factories;

use App\Models\Doctor;
use App\Models\DoctorBreak;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<DoctorBreak>
 */
class DoctorBreakFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'doctor_id' => Doctor::factory(),
            'break_date' => now()->addDay()->toDateString(),
            'start_time' => '10:30',
            'end_time' => '11:30',
        ];
    }
}
