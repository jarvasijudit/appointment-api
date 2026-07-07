<?php

use App\Actions\Availability\GetAvailableSlots;
use App\Models\Appointment;
use App\Models\Availability;
use App\Models\Doctor;
use Illuminate\Support\Carbon;

test('generates slots for the full length of an availability', function () {
    $this->freezeTime();
    $doctor = Doctor::factory()->create();
    Availability::factory()->create([
        'doctor_id' => $doctor->id,
        'starts_at' => now()->addDay()->setTime(9, 0),
        'ends_at' => now()->addDay()->setTime(12, 0),
        'slot_duration_minutes' => 60,
    ]);

    $slots = app(GetAvailableSlots::class)->handle($doctor);

    expect($slots)->toHaveCount(3);
    expect($slots->first()['starts_at']->format('H:i'))->toBe('09:00');
    expect($slots->last()['ends_at']->format('H:i'))->toBe('12:00');
});

test('drops a trailing remainder that does not fill a full slot', function () {
    $this->freezeTime();
    $doctor = Doctor::factory()->create();
    Availability::factory()->create([
        'doctor_id' => $doctor->id,
        'starts_at' => now()->addDay()->setTime(9, 0),
        'ends_at' => now()->addDay()->setTime(10, 15),
        'slot_duration_minutes' => 60,
    ]);

    $slots = app(GetAvailableSlots::class)->handle($doctor);

    expect($slots)->toHaveCount(1);
});

test('excludes slots that overlap a non-cancelled appointment', function () {
    $this->freezeTime();
    $doctor = Doctor::factory()->create();
    Availability::factory()->create([
        'doctor_id' => $doctor->id,
        'starts_at' => now()->addDay()->setTime(9, 0),
        'ends_at' => now()->addDay()->setTime(11, 0),
        'slot_duration_minutes' => 60,
    ]);
    Appointment::factory()->confirmed()->create([
        'doctor_id' => $doctor->id,
        'starts_at' => now()->addDay()->setTime(9, 0),
        'ends_at' => now()->addDay()->setTime(10, 0),
    ]);

    $slots = app(GetAvailableSlots::class)->handle($doctor);

    expect($slots)->toHaveCount(1);
    expect($slots->first()['starts_at']->format('H:i'))->toBe('10:00');
});

test('includes slots that overlap a cancelled appointment', function () {
    $this->freezeTime();
    $doctor = Doctor::factory()->create();
    Availability::factory()->create([
        'doctor_id' => $doctor->id,
        'starts_at' => now()->addDay()->setTime(9, 0),
        'ends_at' => now()->addDay()->setTime(11, 0),
        'slot_duration_minutes' => 60,
    ]);
    Appointment::factory()->cancelled()->create([
        'doctor_id' => $doctor->id,
        'starts_at' => now()->addDay()->setTime(9, 0),
        'ends_at' => now()->addDay()->setTime(10, 0),
    ]);

    $slots = app(GetAvailableSlots::class)->handle($doctor);

    expect($slots)->toHaveCount(2);
});

test('excludes slots that have already started', function () {
    $this->travelTo(Carbon::parse('2026-01-01 09:45:00'));
    $doctor = Doctor::factory()->create();
    Availability::factory()->create([
        'doctor_id' => $doctor->id,
        'starts_at' => Carbon::parse('2026-01-01 09:00:00'),
        'ends_at' => Carbon::parse('2026-01-01 12:00:00'),
        'slot_duration_minutes' => 60,
    ]);

    $slots = app(GetAvailableSlots::class)->handle($doctor);

    expect($slots)->toHaveCount(2);
    expect($slots->first()['starts_at']->format('H:i'))->toBe('10:00');
});

test('excludes an availability that has already fully ended', function () {
    $this->freezeTime();
    $doctor = Doctor::factory()->create();
    Availability::factory()->create([
        'doctor_id' => $doctor->id,
        'starts_at' => now()->subDay()->setTime(9, 0),
        'ends_at' => now()->subDay()->setTime(12, 0),
        'slot_duration_minutes' => 60,
    ]);

    $slots = app(GetAvailableSlots::class)->handle($doctor);

    expect($slots)->toBeEmpty();
});

test('merges slots from multiple availabilities in chronological order', function () {
    $this->freezeTime();
    $doctor = Doctor::factory()->create();
    Availability::factory()->create([
        'doctor_id' => $doctor->id,
        'starts_at' => now()->addDays(2)->setTime(9, 0),
        'ends_at' => now()->addDays(2)->setTime(10, 0),
        'slot_duration_minutes' => 60,
    ]);
    Availability::factory()->create([
        'doctor_id' => $doctor->id,
        'starts_at' => now()->addDay()->setTime(9, 0),
        'ends_at' => now()->addDay()->setTime(10, 0),
        'slot_duration_minutes' => 60,
    ]);

    $slots = app(GetAvailableSlots::class)->handle($doctor);

    expect($slots)->toHaveCount(2);
    expect($slots->first()['starts_at']->isBefore($slots->last()['starts_at']))->toBeTrue();
});

test('does not include another doctor\'s availabilities or appointments', function () {
    $this->freezeTime();
    $doctor = Doctor::factory()->create();
    $otherDoctor = Doctor::factory()->create();
    Availability::factory()->create([
        'doctor_id' => $doctor->id,
        'starts_at' => now()->addDay()->setTime(9, 0),
        'ends_at' => now()->addDay()->setTime(10, 0),
        'slot_duration_minutes' => 60,
    ]);
    Availability::factory()->create([
        'doctor_id' => $otherDoctor->id,
        'starts_at' => now()->addDay()->setTime(9, 0),
        'ends_at' => now()->addDay()->setTime(13, 0),
        'slot_duration_minutes' => 60,
    ]);

    $slots = app(GetAvailableSlots::class)->handle($doctor);

    expect($slots)->toHaveCount(1);
});
