<?php

use App\Http\Controllers\DoctorController;
use Illuminate\Support\Facades\Route;

Route::apiResource('doctors', DoctorController::class);
