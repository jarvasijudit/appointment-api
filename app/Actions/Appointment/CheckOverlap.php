<?php

namespace App\Actions\Appointment;

use App\Enums\AppointmentState;
use App\Models\Appointment;
use Carbon\Carbon;

class CheckOverlap
{
    public function handle(string $column, int $id, Carbon $startsAt, Carbon $endsAt, ?Appointment $ignoring = null): bool
    {
        return Appointment::query()
            ->where($column, $id)
            ->where('state', '!=', AppointmentState::Cancelled)
            ->where('starts_at', '<', $endsAt)
            ->where('ends_at', '>', $startsAt)
            ->when($ignoring, fn ($query, $appointment) => $query->whereKeyNot($appointment->id))
            ->exists();
    }
}
