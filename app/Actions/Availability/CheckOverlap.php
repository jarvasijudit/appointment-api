<?php

namespace App\Actions\Availability;

use App\Models\Availability;
use App\Models\Doctor;
use Carbon\Carbon;

/**
 * Checks whether a time window overlaps with another availability window
 * already scheduled for the same doctor. Distinct from an appointment
 * overlap check (e.g. a patient double-booking), which is a separate
 * business rule.
 */
class CheckOverlap
{
    public function handle(Doctor $doctor, Carbon $startsAt, Carbon $endsAt, ?Availability $ignoring = null): bool
    {
        return Availability::query()
            ->where('doctor_id', $doctor->id)
            ->where('starts_at', '<', $endsAt)
            ->where('ends_at', '>', $startsAt)
            ->when($ignoring, fn ($query, $availability) => $query->whereKeyNot($availability->id))
            ->exists();
    }
}
