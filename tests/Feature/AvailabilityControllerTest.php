<?php

use App\Enums\AppointmentState;
use App\Models\Appointment;
use App\Models\Availability;
use App\Models\Doctor;
use Illuminate\Support\Carbon;

test('index returns list of availabilities', function () {
    Availability::factory()->count(3)->create();

    $response = $this->getJson('/api/availabilities');

    $response->assertOk();
    $response->assertJsonCount(3, 'data');
    $response->assertJsonStructure([
        'data' => [
            '*' => ['id', 'doctor_id', 'starts_at', 'ends_at', 'slot_duration_minutes'],
        ],
    ]);
});

test('show returns a single availability', function () {
    $availability = Availability::factory()->create();

    $response = $this->getJson("/api/availabilities/{$availability->ulid}");

    $response->assertOk();
    $response->assertJsonPath('data.id', $availability->ulid);
    $response->assertJsonPath('data.doctor_id', $availability->doctor->ulid);
});

test('show returns 404 for a non-existent availability', function () {
    $response = $this->getJson('/api/availabilities/does-not-exist');

    $response->assertNotFound();
});

test('store creates an availability', function () {
    $doctor = Doctor::factory()->create();

    $payload = Availability::factory()
        ->make([
            'doctor_id' => $doctor->ulid,
            'starts_at' => now()->addDay()->setTime(9, 0),
            'ends_at' => now()->addDay()->setTime(13, 0),
        ])
        ->toArray();

    $response = $this->postJson('/api/availabilities', $payload);

    $response->assertCreated();
    $response->assertJsonPath('data.doctor_id', $doctor->ulid);
    $response->assertJsonPath('data.slot_duration_minutes', $payload['slot_duration_minutes']);
    $this->assertDatabaseHas('availabilities', [
        'doctor_id' => $doctor->id,
        'slot_duration_minutes' => $payload['slot_duration_minutes'],
    ]);
});

test('store requires doctor_id, starts_at, ends_at and slot_duration_minutes', function () {
    $response = $this->postJson('/api/availabilities', []);

    $response->assertUnprocessable();
    $response->assertJsonValidationErrors(['doctor_id', 'starts_at', 'ends_at', 'slot_duration_minutes']);
});

test('store rejects a non-existent doctor_id', function () {
    $payload = [
        'doctor_id' => 'not-a-real-doctor',
        'starts_at' => now()->addDay(),
        'ends_at' => now()->addDay()->addHours(4),
        'slot_duration_minutes' => 60,
    ];

    $response = $this->postJson('/api/availabilities', $payload);

    $response->assertUnprocessable();
    $response->assertJsonValidationErrors(['doctor_id']);
});

test('store rejects a starts_at that is not in the future', function () {
    $doctor = Doctor::factory()->create();

    $payload = [
        'doctor_id' => $doctor->ulid,
        'starts_at' => now()->subHour(),
        'ends_at' => now()->addHours(3),
        'slot_duration_minutes' => 60,
    ];

    $response = $this->postJson('/api/availabilities', $payload);

    $response->assertUnprocessable();
    $response->assertJsonValidationErrors(['starts_at']);
});

test('store rejects an ends_at before starts_at', function () {
    $doctor = Doctor::factory()->create();
    $startsAt = now()->addDay();

    $payload = [
        'doctor_id' => $doctor->ulid,
        'starts_at' => $startsAt,
        'ends_at' => $startsAt->clone()->subHour(),
        'slot_duration_minutes' => 60,
    ];

    $response = $this->postJson('/api/availabilities', $payload);

    $response->assertUnprocessable();
    $response->assertJsonValidationErrors(['ends_at']);
});

test('store rejects overlapping availability for the same doctor', function () {
    $doctor = Doctor::factory()->create();
    $existing = Availability::factory()->create([
        'doctor_id' => $doctor->id,
        'starts_at' => now()->addDay()->setTime(9, 0),
        'ends_at' => now()->addDay()->setTime(13, 0),
    ]);

    $payload = Availability::factory()
        ->make([
            'doctor_id' => $doctor->ulid,
            'starts_at' => $existing->starts_at->clone()->addHour(),
            'ends_at' => $existing->ends_at->clone()->addHour(),
        ])
        ->toArray();

    $response = $this->postJson('/api/availabilities', $payload);

    $response->assertUnprocessable();
    $response->assertJsonValidationErrors(['starts_at']);
    $this->assertDatabaseCount('availabilities', 1);
});

test('store allows a non-overlapping availability for the same doctor', function () {
    $doctor = Doctor::factory()->create();
    Availability::factory()->create([
        'doctor_id' => $doctor->id,
        'starts_at' => now()->addDay()->setTime(9, 0),
        'ends_at' => now()->addDay()->setTime(13, 0),
    ]);

    $payload = Availability::factory()
        ->make([
            'doctor_id' => $doctor->ulid,
            'starts_at' => now()->addDay()->setTime(13, 0),
            'ends_at' => now()->addDay()->setTime(17, 0),
        ])
        ->toArray();

    $response = $this->postJson('/api/availabilities', $payload);

    $response->assertCreated();
    $this->assertDatabaseCount('availabilities', 2);
});

test('store rejects an ends_at that violates the minimum slot duration', function () {
    $doctor = Doctor::factory()->create();
    $startsAt = now()->addDay()->setTime(9, 0);

    $payload = Availability::factory()
        ->make([
            'doctor_id' => $doctor->ulid,
            'starts_at' => $startsAt,
            'ends_at' => $startsAt->clone()->addMinutes(30),
            'slot_duration_minutes' => 60,
        ])
        ->toArray();

    $response = $this->postJson('/api/availabilities', $payload);

    $response->assertUnprocessable();
    $response->assertJsonValidationErrors(['ends_at']);
    $this->assertDatabaseCount('availabilities', 0);
});

test('store allows an ends_at exactly matching the minimum slot duration', function () {
    $doctor = Doctor::factory()->create();
    $startsAt = now()->addDay()->setTime(9, 0);

    $payload = Availability::factory()
        ->make([
            'doctor_id' => $doctor->ulid,
            'starts_at' => $startsAt,
            'ends_at' => $startsAt->clone()->addMinutes(60),
            'slot_duration_minutes' => 60,
        ])
        ->toArray();

    $response = $this->postJson('/api/availabilities', $payload);

    $response->assertCreated();
});

test('update updates an availability', function () {
    $availability = Availability::factory()->create();

    $response = $this->patchJson("/api/availabilities/{$availability->ulid}", [
        'slot_duration_minutes' => 45,
    ]);

    $response->assertOk();
    $response->assertJsonPath('data.slot_duration_minutes', 45);
    $this->assertDatabaseHas('availabilities', [
        'id' => $availability->id,
        'slot_duration_minutes' => 45,
    ]);
});

test('update rejects overlapping availability for the same doctor, excluding itself', function () {
    $doctor = Doctor::factory()->create();
    $availability = Availability::factory()->create([
        'doctor_id' => $doctor->id,
        'starts_at' => now()->addDay()->setTime(9, 0),
        'ends_at' => now()->addDay()->setTime(13, 0),
    ]);
    $other = Availability::factory()->create([
        'doctor_id' => $doctor->id,
        'starts_at' => now()->addDay()->setTime(14, 0),
        'ends_at' => now()->addDay()->setTime(18, 0),
    ]);

    $response = $this->patchJson("/api/availabilities/{$availability->ulid}", [
        'starts_at' => $other->starts_at->clone()->addHour(),
        'ends_at' => $other->ends_at->clone(),
    ]);

    $response->assertUnprocessable();
    $response->assertJsonValidationErrors(['starts_at']);
});

test('update allows keeping the availability\'s own time range', function () {
    $availability = Availability::factory()->create([
        'starts_at' => now()->addDay()->setTime(9, 0),
        'ends_at' => now()->addDay()->setTime(13, 0),
    ]);

    $response = $this->patchJson("/api/availabilities/{$availability->ulid}", [
        'starts_at' => $availability->starts_at,
        'ends_at' => $availability->ends_at,
    ]);

    $response->assertOk();
});

test('update rejects an ends_at that violates the minimum slot duration', function () {
    $availability = Availability::factory()->create([
        'starts_at' => now()->addDay()->setTime(9, 0),
        'ends_at' => now()->addDay()->setTime(13, 0),
        'slot_duration_minutes' => 60,
    ]);

    $response = $this->patchJson("/api/availabilities/{$availability->ulid}", [
        'starts_at' => $availability->starts_at,
        'ends_at' => $availability->starts_at->clone()->addMinutes(30),
    ]);

    $response->assertUnprocessable();
    $response->assertJsonValidationErrors(['ends_at']);
});

test('update rejects an ends_at before the existing starts_at when starts_at is not sent', function () {
    $availability = Availability::factory()->create([
        'starts_at' => now()->addDay()->setTime(9, 0),
        'ends_at' => now()->addDay()->setTime(13, 0),
    ]);

    $response = $this->patchJson("/api/availabilities/{$availability->ulid}", [
        'ends_at' => $availability->starts_at->clone()->subHour(),
    ]);

    $response->assertUnprocessable();
    $response->assertJsonValidationErrors(['ends_at']);
});

test('update rejects an ends_at that violates the minimum slot duration when starts_at is not sent', function () {
    $availability = Availability::factory()->create([
        'starts_at' => now()->addDay()->setTime(9, 0),
        'ends_at' => now()->addDay()->setTime(13, 0),
        'slot_duration_minutes' => 60,
    ]);

    $response = $this->patchJson("/api/availabilities/{$availability->ulid}", [
        'ends_at' => $availability->starts_at->clone()->addMinutes(30),
    ]);

    $response->assertUnprocessable();
    $response->assertJsonValidationErrors(['ends_at']);
});

test('update returns 404 for a non-existent availability', function () {
    $response = $this->patchJson('/api/availabilities/does-not-exist', [
        'slot_duration_minutes' => 45,
    ]);

    $response->assertNotFound();
});

test('destroy deletes an availability', function () {
    $availability = Availability::factory()->create();

    $response = $this->deleteJson("/api/availabilities/{$availability->ulid}");

    $response->assertNoContent();
    $this->assertDatabaseMissing('availabilities', ['id' => $availability->id]);
    $this->getJson("/api/availabilities/{$availability->ulid}")->assertNotFound();
});

test('available slots returns the doctor\'s bookable slots', function () {
    $this->freezeTime();
    $doctor = Doctor::factory()->create();
    Availability::factory()->create([
        'doctor_id' => $doctor->id,
        'starts_at' => now()->addDay()->setTime(9, 0),
        'ends_at' => now()->addDay()->setTime(12, 0),
        'slot_duration_minutes' => 60,
    ]);

    $response = $this->getJson("/api/doctors/{$doctor->ulid}/available-slots");

    $response->assertOk();
    $response->assertJsonCount(3, 'data');
    $response->assertJsonStructure([
        'data' => [
            '*' => ['starts_at', 'ends_at'],
        ],
    ]);
});

test('available slots excludes slots overlapping a non-cancelled appointment', function () {
    $this->freezeTime();
    $doctor = Doctor::factory()->create();
    Availability::factory()->create([
        'doctor_id' => $doctor->id,
        'starts_at' => now()->addDay()->setTime(9, 0),
        'ends_at' => now()->addDay()->setTime(12, 0),
        'slot_duration_minutes' => 60,
    ]);
    Appointment::factory()->create([
        'doctor_id' => $doctor->id,
        'starts_at' => now()->addDay()->setTime(10, 0),
        'ends_at' => now()->addDay()->setTime(11, 0),
        'state' => AppointmentState::Confirmed,
    ]);

    $response = $this->getJson("/api/doctors/{$doctor->ulid}/available-slots");

    $response->assertOk();
    $response->assertJsonCount(2, 'data');
    $response->assertJsonMissing(['starts_at' => now()->addDay()->setTime(10, 0)]);
});

test('available slots includes slots overlapping a cancelled appointment', function () {
    $this->freezeTime();
    $doctor = Doctor::factory()->create();
    Availability::factory()->create([
        'doctor_id' => $doctor->id,
        'starts_at' => now()->addDay()->setTime(9, 0),
        'ends_at' => now()->addDay()->setTime(12, 0),
        'slot_duration_minutes' => 60,
    ]);
    Appointment::factory()->cancelled()->create([
        'doctor_id' => $doctor->id,
        'starts_at' => now()->addDay()->setTime(10, 0),
        'ends_at' => now()->addDay()->setTime(11, 0),
    ]);

    $response = $this->getJson("/api/doctors/{$doctor->ulid}/available-slots");

    $response->assertOk();
    $response->assertJsonCount(3, 'data');
});

test('available slots excludes slots that have already started', function () {
    $date = Carbon::parse('2026-01-01 09:45:00');
    $this->travelTo($date);

    $doctor = Doctor::factory()->create();
    Availability::factory()->create([
        'doctor_id' => $doctor->id,
        'starts_at' => $date->clone()->setTime(9, 0),
        'ends_at' => $date->clone()->setTime(12, 0),
        'slot_duration_minutes' => 60,
    ]);

    $response = $this->getJson("/api/doctors/{$doctor->ulid}/available-slots");

    $response->assertOk();
    $response->assertJsonCount(2, 'data');
});

test('available slots returns 404 for a non-existent doctor', function () {
    $response = $this->getJson('/api/doctors/does-not-exist/available-slots');

    $response->assertNotFound();
});
