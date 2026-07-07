<?php

namespace App\Actions\Appointment;

use App\Enums\AppointmentState;
use App\Models\Appointment;
use App\Models\Doctor;
use App\Models\Patient;
use Carbon\Carbon;

class CreateAppointment
{
    public function __construct(
        private readonly AssertAppointmentIsBookable $assertAppointmentIsBookable,
    ) {}

    public function handle(array $data): Appointment
    {
        $patient = Patient::query()->where('ulid', $data['patient_id'])->firstOrFail();
        $doctor = Doctor::query()->where('ulid', $data['doctor_id'])->firstOrFail();

        $startsAt = Carbon::parse($data['starts_at']);
        $endsAt = Carbon::parse($data['ends_at']);

        $this->assertAppointmentIsBookable->handle($doctor, $patient, $startsAt, $endsAt);

        return Appointment::create([
            'patient_id' => $patient->id,
            'doctor_id' => $doctor->id,
            'starts_at' => $startsAt,
            'ends_at' => $endsAt,
            'state' => AppointmentState::Pending,
        ]);
    }
}
