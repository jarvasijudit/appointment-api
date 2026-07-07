<?php

use App\Actions\Appointment\AssertAppointmentIsBookable;
use App\Models\Appointment;
use App\Models\Availability;
use App\Models\Doctor;
use App\Models\Patient;
use Illuminate\Validation\ValidationException;

test('passes when the slot is within availability and free of conflicts', function () {
    $doctor = Doctor::factory()->create();
    $patient = Patient::factory()->create();
    Availability::factory()->create([
        'doctor_id' => $doctor->id,
        'starts_at' => now()->addDay()->setTime(9, 0),
        'ends_at' => now()->addDay()->setTime(13, 0),
    ]);

    app(AssertAppointmentIsBookable::class)->handle(
        $doctor,
        $patient,
        now()->addDay()->setTime(9, 0),
        now()->addDay()->setTime(10, 0),
    );
})->throwsNoExceptions();

test('rejects a slot outside the doctor\'s availability', function () {
    $doctor = Doctor::factory()->create();
    $patient = Patient::factory()->create();

    expect(fn () => app(AssertAppointmentIsBookable::class)->handle(
        $doctor,
        $patient,
        now()->addDay()->setTime(9, 0),
        now()->addDay()->setTime(10, 0),
    ))->toThrow(ValidationException::class, 'This time is not within an available slot for this doctor.');
});

test('rejects a slot that overlaps the doctor\'s existing appointment', function () {
    $doctor = Doctor::factory()->create();
    $patient = Patient::factory()->create();
    Availability::factory()->create([
        'doctor_id' => $doctor->id,
        'starts_at' => now()->addDay()->setTime(9, 0),
        'ends_at' => now()->addDay()->setTime(13, 0),
    ]);
    Appointment::factory()->create([
        'doctor_id' => $doctor->id,
        'starts_at' => now()->addDay()->setTime(9, 0),
        'ends_at' => now()->addDay()->setTime(10, 0),
    ]);

    expect(fn () => app(AssertAppointmentIsBookable::class)->handle(
        $doctor,
        $patient,
        now()->addDay()->setTime(9, 30),
        now()->addDay()->setTime(10, 30),
    ))->toThrow(ValidationException::class, 'This doctor already has an appointment at this time.');
});

test('rejects a slot that overlaps the patient\'s appointment with another doctor', function () {
    $doctor = Doctor::factory()->create();
    $otherDoctor = Doctor::factory()->create();
    $patient = Patient::factory()->create();
    Availability::factory()->create([
        'doctor_id' => $doctor->id,
        'starts_at' => now()->addDay()->setTime(9, 0),
        'ends_at' => now()->addDay()->setTime(13, 0),
    ]);
    Appointment::factory()->create([
        'doctor_id' => $otherDoctor->id,
        'patient_id' => $patient->id,
        'starts_at' => now()->addDay()->setTime(9, 0),
        'ends_at' => now()->addDay()->setTime(10, 0),
    ]);

    expect(fn () => app(AssertAppointmentIsBookable::class)->handle(
        $doctor,
        $patient,
        now()->addDay()->setTime(9, 30),
        now()->addDay()->setTime(10, 30),
    ))->toThrow(ValidationException::class, 'This patient already has another appointment at this time.');
});

test('reports the availability error before checking for overlaps', function () {
    $doctor = Doctor::factory()->create();
    $patient = Patient::factory()->create();
    Appointment::factory()->create([
        'doctor_id' => $doctor->id,
        'starts_at' => now()->addDay()->setTime(9, 0),
        'ends_at' => now()->addDay()->setTime(10, 0),
    ]);

    expect(fn () => app(AssertAppointmentIsBookable::class)->handle(
        $doctor,
        $patient,
        now()->addDay()->setTime(9, 0),
        now()->addDay()->setTime(10, 0),
    ))->toThrow(ValidationException::class, 'This time is not within an available slot for this doctor.');
});

test('ignores a cancelled appointment for the doctor when checking overlap', function () {
    $doctor = Doctor::factory()->create();
    $patient = Patient::factory()->create();
    Availability::factory()->create([
        'doctor_id' => $doctor->id,
        'starts_at' => now()->addDay()->setTime(9, 0),
        'ends_at' => now()->addDay()->setTime(13, 0),
    ]);
    Appointment::factory()->cancelled()->create([
        'doctor_id' => $doctor->id,
        'starts_at' => now()->addDay()->setTime(9, 0),
        'ends_at' => now()->addDay()->setTime(10, 0),
    ]);

    app(AssertAppointmentIsBookable::class)->handle(
        $doctor,
        $patient,
        now()->addDay()->setTime(9, 0),
        now()->addDay()->setTime(10, 0),
    );
})->throwsNoExceptions();

test('allows updating an appointment to its own current time slot by ignoring itself', function () {
    $doctor = Doctor::factory()->create();
    $patient = Patient::factory()->create();
    Availability::factory()->create([
        'doctor_id' => $doctor->id,
        'starts_at' => now()->addDay()->setTime(9, 0),
        'ends_at' => now()->addDay()->setTime(13, 0),
    ]);
    $appointment = Appointment::factory()->create([
        'doctor_id' => $doctor->id,
        'patient_id' => $patient->id,
        'starts_at' => now()->addDay()->setTime(9, 0),
        'ends_at' => now()->addDay()->setTime(10, 0),
    ]);

    app(AssertAppointmentIsBookable::class)->handle(
        $doctor,
        $patient,
        now()->addDay()->setTime(9, 0),
        now()->addDay()->setTime(10, 0),
        $appointment,
    );
})->throwsNoExceptions();
