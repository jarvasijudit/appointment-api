<?php

namespace App\Actions\Appointment;

use App\Enums\AppointmentState;
use App\Models\Appointment;
use Illuminate\Validation\ValidationException;

class AssertAppointmentIsCancellable
{
    public function handle(Appointment $appointment): void
    {
        if ($appointment->state !== AppointmentState::Confirmed) {
            return;
        }

        if ($appointment->starts_at->isBefore(now()->addHours(24))) {
            throw ValidationException::withMessages([
                'starts_at' => 'Appointments can only be cancelled at least 24 hours before they start.',
            ]);
        }
    }
}
