<?php

namespace App\Actions\Appointment;

use App\Models\Appointment;
use App\Models\Doctor;
use App\Models\Patient;
use Carbon\Carbon;
use Illuminate\Validation\ValidationException;

class AssertAppointmentIsBookable
{
    public function __construct(
        private readonly CheckOverlap $checkOverlap,
        private readonly CheckWithinAvailability $checkWithinAvailability,
    ) {}

    public function handle(Doctor $doctor, Patient $patient, Carbon $startsAt, Carbon $endsAt, ?Appointment $ignoring = null): void
    {
        if (! $this->checkWithinAvailability->handle($doctor, $startsAt, $endsAt)) {
            throw ValidationException::withMessages([
                'starts_at' => 'This time is not within an available slot for this doctor.',
            ]);
        }

        if ($this->checkOverlap->handle('doctor_id', $doctor->id, $startsAt, $endsAt, $ignoring)) {
            throw ValidationException::withMessages([
                'starts_at' => 'This doctor already has an appointment at this time.',
            ]);
        }

        if ($this->checkOverlap->handle('patient_id', $patient->id, $startsAt, $endsAt, $ignoring)) {
            throw ValidationException::withMessages([
                'starts_at' => 'This patient already has another appointment at this time.',
            ]);
        }
    }
}
