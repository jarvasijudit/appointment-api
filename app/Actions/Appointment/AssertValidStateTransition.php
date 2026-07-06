<?php

namespace App\Actions\Appointment;

use App\Enums\AppointmentState;
use App\Models\Appointment;
use Illuminate\Validation\ValidationException;

class AssertValidStateTransition
{
    public function handle(Appointment $appointment, AppointmentState $state): void
    {
        if ($appointment->state === $state) {
            return;
        }

        if (! $appointment->state->canTransitionTo($state)) {
            throw ValidationException::withMessages([
                'state' => "Cannot transition from {$appointment->state->value} to {$state->value}.",
            ]);
        }
    }
}
