<?php

namespace App\Actions\Availability;

use App\Models\Availability;
use App\Models\Doctor;
use Carbon\Carbon;

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
