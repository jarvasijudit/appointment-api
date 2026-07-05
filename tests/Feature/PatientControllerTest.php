<?php

use App\Models\Patient;

test('index returns list of patients', function () {
    Patient::factory()->count(3)->create();

    $response = $this->getJson('/api/patients');

    $response->assertOk();
    $response->assertJsonCount(3, 'data');
    $response->assertJsonStructure([
        'data' => [
            '*' => ['id', 'name', 'email', 'phone'],
        ],
    ]);
});

test('show returns a single patient', function () {
    $patient = Patient::factory()->create();

    $response = $this->getJson("/api/patients/{$patient->ulid}");

    $response->assertOk();
    $response->assertJsonPath('data.id', $patient->ulid);
    $response->assertJsonPath('data.email', $patient->email);
});

test('show returns 404 for a non-existent patient', function () {
    $response = $this->getJson('/api/patients/does-not-exist');

    $response->assertNotFound();
});

test('store creates a patient', function () {
    $payload = Patient::factory()->make()->toArray();

    $response = $this->postJson('/api/patients', $payload);

    $response->assertJsonPath('data.name', $payload['name']);
    $response->assertJsonPath('data.email', $payload['email']);
    $response->assertJsonPath('data.phone', $payload['phone']);
    $this->assertDatabaseHas('patients', [
        'name' => $payload['name'],
        'email' => $payload['email'],
    ]);
});

test('store requires name, email and phone', function () {
    $response = $this->postJson('/api/patients', []);

    $response->assertUnprocessable();
    $response->assertJsonValidationErrors(['name', 'email', 'phone']);
});

test('store rejects a duplicate email', function () {
    $existing = Patient::factory()->create();
    $payload = Patient::factory()->make(['email' => $existing->email])->toArray();

    $response = $this->postJson('/api/patients', $payload);

    $response->assertUnprocessable();
    $response->assertJsonValidationErrors(['email']);
    $this->assertDatabaseCount('patients', 1);
});

test('update updates a patient', function () {
    $patient = Patient::factory()->create();

    $response = $this->putJson("/api/patients/{$patient->ulid}", [
        'name' => 'Updated Name',
    ]);

    $response->assertOk();
    $response->assertJsonPath('data.name', 'Updated Name');
    $this->assertDatabaseHas('patients', [
        'id' => $patient->id,
        'name' => 'Updated Name',
    ]);
});

test('update allows keeping the patient\'s own email', function () {
    $patient = Patient::factory()->create();

    $response = $this->putJson("/api/patients/{$patient->ulid}", [
        'email' => $patient->email,
    ]);

    $response->assertOk();
});

test('update rejects a duplicate email', function () {
    $patient = Patient::factory()->create();
    $otherPatient = Patient::factory()->create();

    $response = $this->putJson("/api/patients/{$patient->ulid}", [
        'email' => $otherPatient->email,
    ]);

    $response->assertUnprocessable();
    $response->assertJsonValidationErrors(['email']);
});

test('update returns 404 for a non-existent patient', function () {
    $response = $this->putJson('/api/patients/does-not-exist', [
        'name' => 'Updated Name',
    ]);

    $response->assertNotFound();
});

test('destroy deletes a patient', function () {
    $patient = Patient::factory()->create();

    $response = $this->deleteJson("/api/patients/{$patient->ulid}");

    $response->assertNoContent();
    $this->assertSoftDeleted($patient);
    $this->getJson("/api/patients/{$patient->ulid}")->assertNotFound();
});
