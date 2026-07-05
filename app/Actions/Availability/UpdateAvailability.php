<?php

namespace App\Actions\Availability;

use App\Models\Availability;
use App\Models\Doctor;
use Carbon\Carbon;
use Illuminate\Validation\ValidationException;

class UpdateAvailability
{
    public function __construct(
        private readonly CheckOverlap $checkOverlap,
    ) {}

    public function handle(Availability $availability, array $data): Availability
    {
        $doctor = isset($data['doctor_id'])
            ? Doctor::where('ulid', $data['doctor_id'])->firstOrFail()
            : $availability->doctor;

        $startsAt = isset($data['starts_at']) ? Carbon::parse($data['starts_at']) : $availability->starts_at;
        $endsAt = isset($data['ends_at']) ? Carbon::parse($data['ends_at']) : $availability->ends_at;

        if ($this->checkOverlap->handle($doctor, $startsAt, $endsAt, $availability)) {
            throw ValidationException::withMessages([
                'starts_at' => 'This time overlaps with an existing availability for this doctor.',
            ]);
        }

        $availability->update([
            'doctor_id' => $doctor->id,
            'starts_at' => $startsAt,
            'ends_at' => $endsAt,
            'slot_duration_minutes' => $data['slot_duration_minutes'] ?? $availability->slot_duration_minutes,
        ]);

        return $availability;
    }
}
