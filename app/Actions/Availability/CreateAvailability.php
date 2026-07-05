<?php

namespace App\Actions\Availability;

use App\Models\Availability;
use App\Models\Doctor;
use Carbon\Carbon;
use Illuminate\Validation\ValidationException;

class CreateAvailability
{
    public function __construct(
        private readonly CheckOverlap $checkOverlap,
    ) {}

    public function handle(array $data): Availability
    {
        $doctor = Doctor::query()->where('ulid', $data['doctor_id'])->firstOrFail();

        if ($this->checkOverlap->handle($doctor, Carbon::parse($data['starts_at']), Carbon::parse($data['ends_at']))) {
            throw ValidationException::withMessages([
                'starts_at' => 'This time overlaps with an existing availability for this doctor.',
            ]);
        }

        return Availability::create([
            'doctor_id' => $doctor->id,
            'starts_at' => Carbon::parse($data['starts_at']),
            'ends_at' => Carbon::parse($data['ends_at']),
            'slot_duration_minutes' => $data['slot_duration_minutes'],
        ]);
    }
}
