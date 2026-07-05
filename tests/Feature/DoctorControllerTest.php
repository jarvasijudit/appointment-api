<?php

use App\Models\Doctor;

test('index returns list of doctors', function () {
    Doctor::factory()->count(3)->create();

    $response = $this->getJson('/api/doctors');

    $response->assertOk();
    $response->assertJsonCount(3, 'data');
    $response->assertJsonStructure([
        'data' => [
            '*' => ['id', 'name', 'email', 'specialization'],
        ],
    ]);
});

test('show returns a single doctor', function () {
    $doctor = Doctor::factory()->create();

    $response = $this->getJson("/api/doctors/{$doctor->ulid}");

    $response->assertOk();
    $response->assertJsonPath('data.id', $doctor->ulid);
    $response->assertJsonPath('data.email', $doctor->email);
});

test('show returns 404 for a non-existent doctor', function () {
    $response = $this->getJson('/api/doctors/does-not-exist');

    $response->assertNotFound();
});

test('store creates a doctor', function () {
    $payload = Doctor::factory()->make()->toArray();

    $response = $this->postJson('/api/doctors', $payload);

    $response->assertJsonPath('data.name', $payload['name']);
    $response->assertJsonPath('data.email', $payload['email']);
    $response->assertJsonPath('data.specialization', $payload['specialization']);
    $this->assertDatabaseHas('doctors', [
        'name' => $payload['name'],
        'email' => $payload['email'],
    ]);
});

test('store requires name, email and specialization', function () {
    $response = $this->postJson('/api/doctors', []);

    $response->assertUnprocessable();
    $response->assertJsonValidationErrors(['name', 'email', 'specialization']);
});

test('store rejects an invalid specialization', function () {
    $payload = Doctor::factory()->make()->toArray();
    $payload['specialization'] = 'invalid-specialization';

    $response = $this->postJson('/api/doctors', $payload);

    $response->assertUnprocessable();
    $response->assertJsonValidationErrors(['specialization']);
});

test('store rejects a duplicate email', function () {
    $existing = Doctor::factory()->create();
    $payload = Doctor::factory()->make(['email' => $existing->email])->toArray();

    $response = $this->postJson('/api/doctors', $payload);

    $response->assertUnprocessable();
    $response->assertJsonValidationErrors(['email']);
    $this->assertDatabaseCount('doctors', 1);
});

test('update updates a doctor', function () {
    $doctor = Doctor::factory()->create();

    $response = $this->patchJson("/api/doctors/{$doctor->ulid}", [
        'name' => 'Updated Name',
    ]);

    $response->assertOk();
    $response->assertJsonPath('data.name', 'Updated Name');
    $this->assertDatabaseHas('doctors', [
        'id' => $doctor->id,
        'name' => 'Updated Name',
    ]);
});

test('update allows keeping the doctor\'s own email', function () {
    $doctor = Doctor::factory()->create();

    $response = $this->patchJson("/api/doctors/{$doctor->ulid}", [
        'email' => $doctor->email,
    ]);

    $response->assertOk();
});

test('update rejects a duplicate email', function () {
    $doctor = Doctor::factory()->create();
    $otherDoctor = Doctor::factory()->create();

    $response = $this->patchJson("/api/doctors/{$doctor->ulid}", [
        'email' => $otherDoctor->email,
    ]);

    $response->assertUnprocessable();
    $response->assertJsonValidationErrors(['email']);
});

test('update returns 404 for a non-existent doctor', function () {
    $response = $this->patchJson('/api/doctors/does-not-exist', [
        'name' => 'Updated Name',
    ]);

    $response->assertNotFound();
});

test('destroy deletes a doctor', function () {
    $doctor = Doctor::factory()->create();

    $response = $this->deleteJson("/api/doctors/{$doctor->ulid}");

    $response->assertNoContent();
    $this->assertSoftDeleted($doctor);
    $this->getJson("/api/doctors/{$doctor->ulid}")->assertNotFound();
});
