<?php

use App\Actions\Appointment\TransitionAppointmentState;
use App\Enums\AppointmentState;
use App\Models\Appointment;
use Illuminate\Validation\ValidationException;

test('allows a pending appointment to be confirmed', function () {
    $appointment = Appointment::factory()->create();

    $result = app(TransitionAppointmentState::class)->handle($appointment, AppointmentState::Confirmed);

    expect($result->state)->toBe(AppointmentState::Confirmed);
    $this->assertDatabaseHas('appointments', [
        'id' => $appointment->id,
        'state' => AppointmentState::Confirmed,
    ]);
});

test('allows a confirmed appointment to be completed', function () {
    $appointment = Appointment::factory()->confirmed()->create();

    $result = app(TransitionAppointmentState::class)->handle($appointment, AppointmentState::Completed);

    expect($result->state)->toBe(AppointmentState::Completed);
});

test('allows a pending appointment to be cancelled even less than 24 hours before it starts', function () {
    $appointment = Appointment::factory()->create([
        'starts_at' => now()->addHours(12),
        'ends_at' => now()->addHours(12)->addMinutes(30),
    ]);

    $result = app(TransitionAppointmentState::class)->handle($appointment, AppointmentState::Cancelled, 'Patient requested a reschedule.');

    expect($result->state)->toBe(AppointmentState::Cancelled);
    expect($result->cancellation_reason)->toBe('Patient requested a reschedule.');
});

test('allows a confirmed appointment to be cancelled more than 24 hours before it starts', function () {
    $appointment = Appointment::factory()->confirmed()->create([
        'starts_at' => now()->addHours(25),
        'ends_at' => now()->addHours(25)->addMinutes(30),
    ]);

    $result = app(TransitionAppointmentState::class)->handle($appointment, AppointmentState::Cancelled, 'Patient requested a reschedule.');

    expect($result->state)->toBe(AppointmentState::Cancelled);
});

test('rejects cancelling a confirmed appointment less than 24 hours before it starts', function () {
    $appointment = Appointment::factory()->confirmed()->create([
        'starts_at' => now()->addHours(12),
        'ends_at' => now()->addHours(12)->addMinutes(30),
    ]);

    app(TransitionAppointmentState::class)->handle($appointment, AppointmentState::Cancelled, 'Patient requested a reschedule.');
})->throws(ValidationException::class);

test('allows a same-state transition as a no-op', function () {
    $appointment = Appointment::factory()->create();

    $result = app(TransitionAppointmentState::class)->handle($appointment, AppointmentState::Pending);

    expect($result->state)->toBe(AppointmentState::Pending);
});

test('rejects an invalid state transition', function (AppointmentState $from, AppointmentState $to) {
    $appointment = Appointment::factory()->create(['state' => $from]);

    app(TransitionAppointmentState::class)->handle($appointment, $to);
})->throws(ValidationException::class)->with([
    'pending -> completed' => [AppointmentState::Pending, AppointmentState::Completed],
    'confirmed -> pending' => [AppointmentState::Confirmed, AppointmentState::Pending],
    'completed -> pending' => [AppointmentState::Completed, AppointmentState::Pending],
    'completed -> confirmed' => [AppointmentState::Completed, AppointmentState::Confirmed],
    'completed -> cancelled' => [AppointmentState::Completed, AppointmentState::Cancelled],
    'cancelled -> pending' => [AppointmentState::Cancelled, AppointmentState::Pending],
    'cancelled -> confirmed' => [AppointmentState::Cancelled, AppointmentState::Confirmed],
    'cancelled -> completed' => [AppointmentState::Cancelled, AppointmentState::Completed],
]);
