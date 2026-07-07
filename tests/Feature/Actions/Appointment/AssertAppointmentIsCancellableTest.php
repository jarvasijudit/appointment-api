<?php

use App\Actions\Appointment\AssertAppointmentIsCancellable;
use App\Models\Appointment;
use Illuminate\Validation\ValidationException;

test('allows cancelling a pending appointment starting in less than 24 hours', function () {
    $appointment = Appointment::factory()->create([
        'starts_at' => now()->addHours(12),
        'ends_at' => now()->addHours(12)->addMinutes(30),
    ]);

    app(AssertAppointmentIsCancellable::class)->handle($appointment);
})->throwsNoExceptions();

test('allows cancelling a confirmed appointment starting in more than 24 hours', function () {
    $appointment = Appointment::factory()->confirmed()->create([
        'starts_at' => now()->addHours(25),
        'ends_at' => now()->addHours(25)->addMinutes(30),
    ]);

    app(AssertAppointmentIsCancellable::class)->handle($appointment);
})->throwsNoExceptions();

test('rejects cancelling a confirmed appointment starting in less than 24 hours', function () {
    $appointment = Appointment::factory()->confirmed()->create([
        'starts_at' => now()->addHours(12),
        'ends_at' => now()->addHours(12)->addMinutes(30),
    ]);

    app(AssertAppointmentIsCancellable::class)->handle($appointment);
})->throws(ValidationException::class);

test('allows cancelling a confirmed appointment exactly at the 24 hour boundary', function () {
    $this->freezeSecond();

    $startsAt = now()->addHours(24);

    $appointment = Appointment::factory()->confirmed()->create([
        'starts_at' => $startsAt,
        'ends_at' => $startsAt->clone()->addMinutes(30),
    ]);

    app(AssertAppointmentIsCancellable::class)->handle($appointment);
})->throwsNoExceptions();
