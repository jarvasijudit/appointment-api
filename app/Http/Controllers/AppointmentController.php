<?php

namespace App\Http\Controllers;

use App\Actions\Appointment\CreateAppointment;
use App\Actions\Appointment\TransitionAppointmentState;
use App\Actions\Appointment\UpdateAppointment;
use App\Enums\AppointmentState;
use App\Http\Requests\CancelAppointmentRequest;
use App\Http\Requests\IndexAppointmentRequest;
use App\Http\Requests\StoreAppointmentRequest;
use App\Http\Requests\UpdateAppointmentRequest;
use App\Http\Resources\AppointmentResource;
use App\Models\Appointment;
use App\Models\Patient;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class AppointmentController extends Controller
{
    public function index(IndexAppointmentRequest $request, Patient $patient): AnonymousResourceCollection
    {
        return AppointmentResource::collection(
            $patient->appointments()
                ->with(['patient', 'doctor'])
                ->when($request->filled('date'), fn ($query) => $query->whereDate('starts_at', $request->date('date')))
                ->when($request->filled('state'), fn ($query) => $query->where('state', $request->input('state')))
                ->paginate()
        );
    }

    public function show(Appointment $appointment): AppointmentResource
    {
        return AppointmentResource::make($appointment->load(['patient', 'doctor']));
    }

    public function store(StoreAppointmentRequest $request, CreateAppointment $createAppointment): AppointmentResource
    {
        $appointment = $createAppointment->handle($request->validated());

        return AppointmentResource::make($appointment);
    }

    public function update(UpdateAppointmentRequest $request, Appointment $appointment, UpdateAppointment $updateAppointment): AppointmentResource
    {
        $appointment = $updateAppointment->handle($appointment, $request->validated());

        return AppointmentResource::make($appointment);
    }

    public function confirm(Appointment $appointment, TransitionAppointmentState $transitionAppointmentState): AppointmentResource
    {
        $appointment = $transitionAppointmentState->handle($appointment, AppointmentState::Confirmed);

        return AppointmentResource::make($appointment);
    }

    public function cancel(CancelAppointmentRequest $request, Appointment $appointment, TransitionAppointmentState $transitionAppointmentState): AppointmentResource
    {
        $appointment = $transitionAppointmentState->handle($appointment, AppointmentState::Cancelled, $request->validated('cancellation_reason'));

        return AppointmentResource::make($appointment);
    }

    public function complete(Appointment $appointment, TransitionAppointmentState $transitionAppointmentState): AppointmentResource
    {
        $appointment = $transitionAppointmentState->handle($appointment, AppointmentState::Completed);

        return AppointmentResource::make($appointment);
    }
}
