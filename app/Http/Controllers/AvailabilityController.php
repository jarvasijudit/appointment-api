<?php

namespace App\Http\Controllers;

use App\Actions\Availability\CreateAvailability;
use App\Actions\Availability\GetAvailableSlots;
use App\Actions\Availability\UpdateAvailability;
use App\Http\Requests\StoreAvailabilityRequest;
use App\Http\Requests\UpdateAvailabilityRequest;
use App\Http\Resources\AvailabilityResource;
use App\Http\Resources\AvailableSlotResource;
use App\Models\Availability;
use App\Models\Doctor;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Http\Response;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Pagination\Paginator;

class AvailabilityController extends Controller
{
    public function index(): AnonymousResourceCollection
    {
        return AvailabilityResource::collection(Availability::query()->with('doctor')->paginate());
    }

    public function store(StoreAvailabilityRequest $request, CreateAvailability $createAvailability): AvailabilityResource
    {
        $availability = $createAvailability->handle($request->validated());

        return AvailabilityResource::make($availability);
    }

    public function show(Availability $availability): AvailabilityResource
    {
        return AvailabilityResource::make($availability);
    }

    public function update(UpdateAvailabilityRequest $request, Availability $availability, UpdateAvailability $updateAvailability): AvailabilityResource
    {
        $availability = $updateAvailability->handle($availability, $request->validated());

        return AvailabilityResource::make($availability);
    }

    public function destroy(Availability $availability): Response
    {
        $availability->delete();

        return response()->noContent();
    }

    /**
     * List the doctor's free, bookable slots (future, not overlapping an
     * existing non-cancelled appointment), paginated.
     */
    public function availableSlots(Doctor $doctor, GetAvailableSlots $getAvailableSlots): AnonymousResourceCollection
    {
        $availableSlots = $getAvailableSlots->handle($doctor);

        $perPage = 15;
        $page = Paginator::resolveCurrentPage();

        $paginated = new LengthAwarePaginator(
            $availableSlots->forPage($page, $perPage)->values(),
            $availableSlots->count(),
            $perPage,
            $page,
            ['path' => Paginator::resolveCurrentPath()],
        );

        return AvailableSlotResource::collection($paginated);
    }
}
