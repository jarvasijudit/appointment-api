<?php

namespace Database\Seeders;

use App\Models\Appointment;
use App\Models\Availability;
use App\Models\Doctor;
use App\Models\Patient;
use Carbon\Carbon;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        $doctors = Doctor::factory(10)->create();
        $patients = Patient::factory(20)->create();

        $doctors->each(function (Doctor $doctor) use ($patients) {
            $daysOffset = rand(1, 5);
            $availabilities = collect();

            $availabilities->push(Availability::factory()
                ->for($doctor)
                ->create([
                    'starts_at' => now()->addDays($daysOffset)->setTime(9, 0),
                    'ends_at' => now()->addDays($daysOffset)->setTime(19, 0),
                    'slot_duration_minutes' => 30,
                ]));

            $availabilities->push(Availability::factory()
                ->for($doctor)
                ->create([
                    'starts_at' => now()->addDays($daysOffset + 1)->setTime(9, 0),
                    'ends_at' => now()->addDays($daysOffset + 1)->setTime(12, 0),
                    'slot_duration_minutes' => 30,
                ]));

            $patients->random(3)->values()->each(function (Patient $patient, int $index) use ($availabilities) {
                $availabilities->each(function (Availability $availability) use ($patient, $index) {
                    $appointmentDateTime = Carbon::parse($availability->starts_at)->addMinutes($index * $availability->slot_duration_minutes);

                    Appointment::factory()
                        ->for($patient)
                        ->for($availability->doctor)
                        ->create([
                            'starts_at' => $appointmentDateTime,
                            'ends_at' => $appointmentDateTime->copy()->addMinutes($availability->slot_duration_minutes),
                        ]);
                });
            });

        });

    }
}
