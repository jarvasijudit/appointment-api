<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreDoctorRequest;
use App\Http\Requests\UpdateDoctorRequest;
use App\Http\Resources\DoctorResource;
use App\Models\Doctor;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Http\Response;

class DoctorController extends Controller
{
    public function index(): AnonymousResourceCollection
    {
        return DoctorResource::collection(Doctor::query()->paginate());
    }

    public function store(StoreDoctorRequest $request): DoctorResource
    {
        $doctor = Doctor::create($request->validated());

        return DoctorResource::make($doctor);
    }

    public function show(Doctor $doctor): DoctorResource
    {
        return DoctorResource::make($doctor);
    }

    public function update(UpdateDoctorRequest $request, Doctor $doctor): DoctorResource
    {
        $doctor->update($request->validated());

        return DoctorResource::make($doctor);
    }

    public function destroy(Doctor $doctor): Response
    {
        $doctor->delete();

        return response()->noContent();
    }
}
