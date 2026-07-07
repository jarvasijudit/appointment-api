<?php

namespace App\Actions\Appointment;

use App\Models\Availability;
use App\Models\Doctor;
use Carbon\Carbon;

class CheckWithinAvailability
{
    public function handle(Doctor $doctor, Carbon $startsAt, Carbon $endsAt): bool
    {
        return Availability::query()
            ->where('doctor_id', $doctor->id)
            ->where('starts_at', '<=', $startsAt)
            ->where('ends_at', '>=', $endsAt)
            ->exists();
    }
}
