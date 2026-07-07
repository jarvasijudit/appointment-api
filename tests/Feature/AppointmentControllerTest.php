<?php

use App\Enums\AppointmentState;
use App\Models\Appointment;
use App\Models\Availability;
use App\Models\Doctor;
use App\Models\Patient;

test('index returns a patient\'s appointments', function () {
    $patient = Patient::factory()->create();
    Appointment::factory()->count(3)->create(['patient_id' => $patient->id]);
    Appointment::factory()->count(2)->create();

    $response = $this->getJson("/api/patients/{$patient->ulid}/appointments");

    $response->assertOk();
    $response->assertJsonCount(3, 'data');
    $response->assertJsonStructure([
        'data' => [
            '*' => ['id', 'patient_id', 'doctor_id', 'starts_at', 'ends_at', 'state', 'cancellation_reason'],
        ],
    ]);
});

test('index returns 404 for a non-existent patient', function () {
    $response = $this->getJson('/api/patients/does-not-exist/appointments');

    $response->assertNotFound();
});

test('index filters a patient\'s appointments by date', function () {
    $patient = Patient::factory()->create();
    $matching = Appointment::factory()->create([
        'patient_id' => $patient->id,
        'starts_at' => now()->addDay()->setTime(9, 0),
        'ends_at' => now()->addDay()->setTime(10, 0),
    ]);
    Appointment::factory()->create([
        'patient_id' => $patient->id,
        'starts_at' => now()->addDays(2)->setTime(9, 0),
        'ends_at' => now()->addDays(2)->setTime(10, 0),
    ]);

    $response = $this->getJson("/api/patients/{$patient->ulid}/appointments?date=".now()->addDay()->toDateString());

    $response->assertOk();
    $response->assertJsonCount(1, 'data');
    $response->assertJsonPath('data.0.id', $matching->ulid);
});

test('index filters a patient\'s appointments by state', function () {
    $patient = Patient::factory()->create();
    Appointment::factory()->count(2)->create(['patient_id' => $patient->id]);
    Appointment::factory()->confirmed()->create(['patient_id' => $patient->id]);

    $response = $this->getJson("/api/patients/{$patient->ulid}/appointments?state=confirmed");

    $response->assertOk();
    $response->assertJsonCount(1, 'data');
    $response->assertJsonPath('data.0.state', AppointmentState::Confirmed->value);
});

test('index rejects an invalid state filter', function () {
    $patient = Patient::factory()->create();

    $response = $this->getJson("/api/patients/{$patient->ulid}/appointments?state=not-a-real-state");

    $response->assertUnprocessable();
    $response->assertJsonValidationErrors(['state']);
});

test('show returns an appointment', function () {
    $appointment = Appointment::factory()->create();

    $response = $this->getJson("/api/appointments/{$appointment->ulid}");

    $response->assertOk();
    $response->assertJsonPath('data.id', $appointment->ulid);
    $response->assertJsonStructure([
        'data' => ['id', 'patient_id', 'doctor_id', 'starts_at', 'ends_at', 'state', 'cancellation_reason'],
    ]);
});

test('show returns 404 for a non-existent appointment', function () {
    $response = $this->getJson('/api/appointments/does-not-exist');

    $response->assertNotFound();
});

test('store creates an appointment', function () {
    $doctor = Doctor::factory()->create();
    $patient = Patient::factory()->create();
    Availability::factory()->create([
        'doctor_id' => $doctor->id,
        'starts_at' => now()->addDay()->setTime(9, 0),
        'ends_at' => now()->addDay()->setTime(13, 0),
    ]);

    $payload = [
        'patient_id' => $patient->ulid,
        'doctor_id' => $doctor->ulid,
        'starts_at' => now()->addDay()->setTime(9, 0),
        'ends_at' => now()->addDay()->setTime(10, 0),
    ];

    $response = $this->postJson('/api/appointments', $payload);

    $response->assertCreated();
    $response->assertJsonPath('data.patient_id', $patient->ulid);
    $response->assertJsonPath('data.doctor_id', $doctor->ulid);
    $response->assertJsonPath('data.state', AppointmentState::Pending->value);
    $this->assertDatabaseHas('appointments', [
        'patient_id' => $patient->id,
        'doctor_id' => $doctor->id,
        'state' => AppointmentState::Pending,
    ]);
});

test('store requires patient_id, doctor_id, starts_at and ends_at', function () {
    $response = $this->postJson('/api/appointments', []);

    $response->assertUnprocessable();
    $response->assertJsonValidationErrors(['patient_id', 'doctor_id', 'starts_at', 'ends_at']);
});

test('store rejects a starts_at that is not in the future', function () {
    $doctor = Doctor::factory()->create();
    $patient = Patient::factory()->create();

    $payload = [
        'patient_id' => $patient->ulid,
        'doctor_id' => $doctor->ulid,
        'starts_at' => now()->subHour(),
        'ends_at' => now()->addHour(),
    ];

    $response = $this->postJson('/api/appointments', $payload);

    $response->assertUnprocessable();
    $response->assertJsonValidationErrors(['starts_at']);
});

test('store rejects a time outside the doctor\'s availability', function () {
    $doctor = Doctor::factory()->create();
    $patient = Patient::factory()->create();

    $payload = [
        'patient_id' => $patient->ulid,
        'doctor_id' => $doctor->ulid,
        'starts_at' => now()->addDay()->setTime(9, 0),
        'ends_at' => now()->addDay()->setTime(10, 0),
    ];

    $response = $this->postJson('/api/appointments', $payload);

    $response->assertUnprocessable();
    $response->assertJsonValidationErrors(['starts_at']);
    $this->assertDatabaseCount('appointments', 0);
});

test('store rejects an overlapping appointment for the same doctor', function () {
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

    $payload = [
        'patient_id' => $patient->ulid,
        'doctor_id' => $doctor->ulid,
        'starts_at' => now()->addDay()->setTime(9, 30),
        'ends_at' => now()->addDay()->setTime(10, 30),
    ];

    $response = $this->postJson('/api/appointments', $payload);

    $response->assertUnprocessable();
    $response->assertJsonValidationErrors(['starts_at']);
    $this->assertDatabaseCount('appointments', 1);
});

test('store rejects an overlapping appointment for the same patient', function () {
    $doctor = Doctor::factory()->create();
    $otherDoctor = Doctor::factory()->create();
    $patient = Patient::factory()->create();
    Availability::factory()->create([
        'doctor_id' => $otherDoctor->id,
        'starts_at' => now()->addDay()->setTime(9, 0),
        'ends_at' => now()->addDay()->setTime(13, 0),
    ]);
    Appointment::factory()->create([
        'patient_id' => $patient->id,
        'doctor_id' => $doctor->id,
        'starts_at' => now()->addDay()->setTime(9, 0),
        'ends_at' => now()->addDay()->setTime(10, 0),
    ]);

    $payload = [
        'patient_id' => $patient->ulid,
        'doctor_id' => $otherDoctor->ulid,
        'starts_at' => now()->addDay()->setTime(9, 30),
        'ends_at' => now()->addDay()->setTime(10, 30),
    ];

    $response = $this->postJson('/api/appointments', $payload);

    $response->assertUnprocessable();
    $response->assertJsonValidationErrors(['starts_at']);
    $this->assertDatabaseCount('appointments', 1);
});

test('store allows booking over a cancelled appointment\'s time slot', function () {
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

    $payload = [
        'patient_id' => $patient->ulid,
        'doctor_id' => $doctor->ulid,
        'starts_at' => now()->addDay()->setTime(9, 0),
        'ends_at' => now()->addDay()->setTime(10, 0),
    ];

    $response = $this->postJson('/api/appointments', $payload);

    $response->assertCreated();
    $this->assertDatabaseCount('appointments', 2);
});

test('update updates an appointment', function () {
    $doctor = Doctor::factory()->create();
    Availability::factory()->create([
        'doctor_id' => $doctor->id,
        'starts_at' => now()->addDay()->setTime(9, 0),
        'ends_at' => now()->addDay()->setTime(13, 0),
    ]);
    $appointment = Appointment::factory()->create([
        'doctor_id' => $doctor->id,
        'starts_at' => now()->addDay()->setTime(9, 0),
        'ends_at' => now()->addDay()->setTime(10, 0),
    ]);

    $response = $this->patchJson("/api/appointments/{$appointment->ulid}", [
        'starts_at' => now()->addDay()->setTime(11, 0),
        'ends_at' => now()->addDay()->setTime(12, 0),
    ]);

    $response->assertOk();
    $response->assertJsonPath('data.id', $appointment->ulid);
    $this->assertDatabaseHas('appointments', [
        'id' => $appointment->id,
        'starts_at' => now()->addDay()->setTime(11, 0),
        'ends_at' => now()->addDay()->setTime(12, 0),
    ]);
});

test('update rejects moving into an unavailable slot', function () {
    $appointment = Appointment::factory()->create();

    $response = $this->patchJson("/api/appointments/{$appointment->ulid}", [
        'starts_at' => now()->addDays(2)->setTime(9, 0),
        'ends_at' => now()->addDays(2)->setTime(10, 0),
    ]);

    $response->assertUnprocessable();
    $response->assertJsonValidationErrors(['starts_at']);
});

test('update rejects an overlapping appointment for the same doctor', function () {
    $doctor = Doctor::factory()->create();
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
    $appointment = Appointment::factory()->create([
        'doctor_id' => $doctor->id,
        'starts_at' => now()->addDay()->setTime(11, 0),
        'ends_at' => now()->addDay()->setTime(12, 0),
    ]);

    $response = $this->patchJson("/api/appointments/{$appointment->ulid}", [
        'starts_at' => now()->addDay()->setTime(9, 30),
        'ends_at' => now()->addDay()->setTime(10, 30),
    ]);

    $response->assertUnprocessable();
    $response->assertJsonValidationErrors(['starts_at']);
});

test('update returns 404 for a non-existent appointment', function () {
    $response = $this->patchJson('/api/appointments/does-not-exist', [
        'starts_at' => now()->addDay(),
    ]);

    $response->assertNotFound();
});

test('confirm transitions a pending appointment to confirmed', function () {
    $appointment = Appointment::factory()->create();

    $response = $this->postJson("/api/appointments/{$appointment->ulid}/confirm");

    $response->assertOk();
    $response->assertJsonPath('data.state', AppointmentState::Confirmed->value);
    $this->assertDatabaseHas('appointments', [
        'id' => $appointment->id,
        'state' => AppointmentState::Confirmed,
    ]);
});

test('confirm rejects an invalid transition from a completed appointment', function () {
    $appointment = Appointment::factory()->completed()->create();

    $response = $this->postJson("/api/appointments/{$appointment->ulid}/confirm");

    $response->assertUnprocessable();
    $response->assertJsonValidationErrors(['state']);
});

test('confirm returns 404 for a non-existent appointment', function () {
    $response = $this->postJson('/api/appointments/does-not-exist/confirm');

    $response->assertNotFound();
});

test('cancel rejects an invalid transition from a completed appointment', function () {
    $appointment = Appointment::factory()->completed()->create();

    $response = $this->postJson("/api/appointments/{$appointment->ulid}/cancel", [
        'cancellation_reason' => 'Patient requested a reschedule.',
    ]);

    $response->assertUnprocessable();
    $response->assertJsonValidationErrors(['state']);
});

test('cancel rejects a confirmed appointment starting in less than 24 hours', function () {
    $appointment = Appointment::factory()->confirmed()->create([
        'starts_at' => now()->addHours(12),
        'ends_at' => now()->addHours(12)->addMinutes(30),
    ]);

    $response = $this->postJson("/api/appointments/{$appointment->ulid}/cancel", [
        'cancellation_reason' => 'Patient requested a reschedule.',
    ]);

    $response->assertUnprocessable();
    $response->assertJsonValidationErrors(['starts_at']);
    $this->assertDatabaseHas('appointments', [
        'id' => $appointment->id,
        'state' => AppointmentState::Confirmed,
    ]);
});

test('cancel allows a confirmed appointment starting in more than 24 hours', function () {
    $appointment = Appointment::factory()->confirmed()->create([
        'starts_at' => now()->addHours(25),
        'ends_at' => now()->addHours(25)->addMinutes(30),
    ]);

    $response = $this->postJson("/api/appointments/{$appointment->ulid}/cancel", [
        'cancellation_reason' => 'Patient requested a reschedule.',
    ]);

    $response->assertOk();
    $response->assertJsonPath('data.state', AppointmentState::Cancelled->value);
});

test('cancel allows a pending appointment starting in less than 24 hours', function () {
    $appointment = Appointment::factory()->create([
        'starts_at' => now()->addHours(12),
        'ends_at' => now()->addHours(12)->addMinutes(30),
    ]);

    $response = $this->postJson("/api/appointments/{$appointment->ulid}/cancel", [
        'cancellation_reason' => 'Patient requested a reschedule.',
    ]);

    $response->assertOk();
    $response->assertJsonPath('data.state', AppointmentState::Cancelled->value);
});

test('cancel returns 404 for a non-existent appointment', function () {
    $response = $this->postJson('/api/appointments/does-not-exist/cancel', [
        'cancellation_reason' => 'Patient requested a reschedule.',
    ]);

    $response->assertNotFound();
});

test('complete transitions a confirmed appointment to completed', function () {
    $appointment = Appointment::factory()->confirmed()->create();

    $response = $this->postJson("/api/appointments/{$appointment->ulid}/complete");

    $response->assertOk();
    $response->assertJsonPath('data.state', AppointmentState::Completed->value);
    $this->assertDatabaseHas('appointments', [
        'id' => $appointment->id,
        'state' => AppointmentState::Completed,
    ]);
});

test('complete rejects an invalid transition from a pending appointment', function () {
    $appointment = Appointment::factory()->create();

    $response = $this->postJson("/api/appointments/{$appointment->ulid}/complete");

    $response->assertUnprocessable();
    $response->assertJsonValidationErrors(['state']);
});

test('complete returns 404 for a non-existent appointment', function () {
    $response = $this->postJson('/api/appointments/does-not-exist/complete');

    $response->assertNotFound();
});
