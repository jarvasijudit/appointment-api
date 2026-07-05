<?php

namespace App\Actions\Availability;

use App\Enums\AppointmentState;
use App\Models\Appointment;
use App\Models\Availability;
use App\Models\Doctor;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;

class GetAvailableSlots
{
    /**
     * @return Collection<int, array{starts_at: Carbon, ends_at: Carbon}>
     */
    public function handle(Doctor $doctor): Collection
    {
        $bookedRanges = Appointment::query()
            ->where('doctor_id', $doctor->id)
            ->where('state', '!=', AppointmentState::Cancelled)
            ->where('ends_at', '>', now())
            ->get(['starts_at', 'ends_at']);

        return Availability::query()
            ->where('doctor_id', $doctor->id)
            ->where('ends_at', '>', now())
            ->orderBy('starts_at')
            ->get()
            ->flatMap($this->generateSlots(...))
            ->reject(fn (array $slot) => $slot['starts_at']->isPast())
            ->reject(fn (array $slot) => $bookedRanges->contains(
                fn (Appointment $appointment) => $slot['starts_at']->lessThan($appointment->ends_at)
                    && $slot['ends_at']->greaterThan($appointment->starts_at)
            ))
            ->values();
    }

    /**
     * @return Collection<int, array{starts_at: Carbon, ends_at: Carbon}>
     */
    private function generateSlots(Availability $availability): Collection
    {
        $slots = collect();
        $cursor = $availability->starts_at->copy();

        while ($cursor->copy()->addMinutes($availability->slot_duration_minutes)->lessThanOrEqualTo($availability->ends_at)) {
            $slots->push([
                'starts_at' => $cursor->copy(),
                'ends_at' => $cursor->copy()->addMinutes($availability->slot_duration_minutes),
            ]);

            $cursor->addMinutes($availability->slot_duration_minutes);
        }

        return $slots;
    }
}
