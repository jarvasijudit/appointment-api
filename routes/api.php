<?php

use App\Http\Controllers\AvailabilityController;
use App\Http\Controllers\DoctorController;
use App\Http\Controllers\PatientController;
use Illuminate\Support\Facades\Route;

Route::apiResource('doctors', DoctorController::class);
Route::apiResource('patients', PatientController::class);
Route::apiResource('availabilities', AvailabilityController::class);
Route::get('doctors/{doctor}/available-slots', [AvailabilityController::class, 'availableSlots'])
    ->name('doctors.available-slots');
