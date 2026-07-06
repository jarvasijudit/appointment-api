<?php

namespace App\Actions\Appointment;

use App\Models\Appointment;
use App\Models\Doctor;
use Carbon\Carbon;

class UpdateAppointment
{
    public function __construct(
        private readonly AssertAppointmentIsBookable $assertAppointmentIsBookable,
    ) {}

    public function handle(Appointment $appointment, array $data): Appointment
    {
        $doctor = isset($data['doctor_id'])
            ? Doctor::query()->where('ulid', $data['doctor_id'])->firstOrFail()
            : $appointment->doctor;

        $startsAt = isset($data['starts_at']) ? Carbon::parse($data['starts_at']) : $appointment->starts_at;
        $endsAt = isset($data['ends_at']) ? Carbon::parse($data['ends_at']) : $appointment->ends_at;

        $scheduleChanged = $doctor->isNot($appointment->doctor)
            || ! $startsAt->equalTo($appointment->starts_at)
            || ! $endsAt->equalTo($appointment->ends_at);

        if ($scheduleChanged) {
            $this->assertAppointmentIsBookable->handle($doctor, $appointment->patient, $startsAt, $endsAt, $appointment);
        }

        $appointment->update([
            'doctor_id' => $doctor->id,
            'starts_at' => $startsAt,
            'ends_at' => $endsAt,
        ]);

        return $appointment;
    }
}
