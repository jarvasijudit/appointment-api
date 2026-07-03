<?php

namespace App\Http\Controllers;

use App\Http\Requests\StorePatientRequest;
use App\Http\Requests\UpdatePatientRequest;
use App\Http\Resources\PatientResource;
use App\Models\Patient;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Http\Response;

class PatientController extends Controller
{
    public function index(): AnonymousResourceCollection
    {
        return PatientResource::collection(Patient::query()->paginate());
    }

    public function store(StorePatientRequest $request): PatientResource
    {
        $patient = Patient::create($request->validated());

        return PatientResource::make($patient);
    }

    public function show(Patient $patient): PatientResource
    {
        return PatientResource::make($patient);
    }

    public function update(UpdatePatientRequest $request, Patient $patient): PatientResource
    {
        $patient->update($request->validated());

        return PatientResource::make($patient);
    }

    public function destroy(Patient $patient): Response
    {
        $patient->delete();

        return response()->noContent();
    }
}
