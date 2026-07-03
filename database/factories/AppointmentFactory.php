<?php

namespace Database\Factories;

use App\Enums\AppointmentState;
use App\Models\Appointment;
use App\Models\Doctor;
use App\Models\Patient;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Appointment>
 */
class AppointmentFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $startsAt = now()->addDays(rand(1, 7))->setTime(rand(8, 16), 0);

        return [
            'patient_id' => Patient::factory(),
            'doctor_id' => Doctor::factory(),
            'starts_at' => $startsAt,
            'ends_at' => $startsAt->clone()->addMinutes(30),
            'state' => AppointmentState::Pending,
        ];
    }

    public function confirmed(): static
    {
        return $this->state(['state' => AppointmentState::Confirmed]);
    }

    public function completed(): static
    {
        return $this->state(['state' => AppointmentState::Completed]);
    }

    public function cancelled(): static
    {
        return $this->state([
            'state' => AppointmentState::Cancelled,
            'cancellation_reason' => fake()->sentence(),
        ]);
    }
}
