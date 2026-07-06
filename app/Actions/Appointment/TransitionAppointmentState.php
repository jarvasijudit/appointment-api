<?php

namespace App\Actions\Appointment;

use App\Enums\AppointmentState;
use App\Models\Appointment;

class TransitionAppointmentState
{
    public function __construct(
        private readonly AssertValidStateTransition $assertValidStateTransition,
        private readonly AssertAppointmentIsCancellable $assertAppointmentIsCancellable,
    ) {}

    public function handle(Appointment $appointment, AppointmentState $state, ?string $cancellationReason = null): Appointment
    {
        $this->assertValidStateTransition->handle($appointment, $state);

        if ($state === AppointmentState::Cancelled) {
            $this->assertAppointmentIsCancellable->handle($appointment);
        }

        $appointment->update([
            'state' => $state,
            'cancellation_reason' => $cancellationReason,
        ]);

        return $appointment;
    }
}
