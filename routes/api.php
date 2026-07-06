<?php

use App\Http\Controllers\AppointmentController;
use App\Http\Controllers\AvailabilityController;
use App\Http\Controllers\DoctorController;
use App\Http\Controllers\PatientController;
use Illuminate\Support\Facades\Route;

Route::apiResource('doctors', DoctorController::class);

Route::apiResource('patients', PatientController::class);

Route::apiResource('availabilities', AvailabilityController::class);
Route::get('doctors/{doctor}/available-slots', [AvailabilityController::class, 'availableSlots'])
    ->name('doctors.available-slots');

Route::apiResource('appointments', AppointmentController::class)->only(['show', 'store', 'update']);
Route::get('patients/{patient}/appointments', [AppointmentController::class, 'index'])
    ->name('patients.appointments');
Route::prefix('appointments/{appointment}')->name('appointments.')->group(function () {
    Route::post('confirm', [AppointmentController::class, 'confirm'])->name('confirm');
    Route::post('cancel', [AppointmentController::class, 'cancel'])->name('cancel');
    Route::post('complete', [AppointmentController::class, 'complete'])->name('complete');
});
