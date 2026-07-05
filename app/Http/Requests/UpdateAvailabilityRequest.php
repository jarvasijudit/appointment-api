<?php

namespace App\Http\Requests;

use App\Models\Availability;
use App\Rules\MinimumSlotDuration;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class UpdateAvailabilityRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        /** @var Availability $availability */
        $availability = $this->route('availability');

        $startsAt = $this->input('starts_at', $availability->starts_at);

        return [
            'doctor_id' => ['sometimes', 'ulid', 'exists:doctors,ulid'],
            'starts_at' => ['sometimes', 'date', 'after:now'],
            'ends_at' => [
                'sometimes',
                'date',
                "after:{$startsAt}",
                new MinimumSlotDuration(
                    $availability->starts_at,
                    $availability->ends_at,
                    $availability->slot_duration_minutes
                ),
            ],
            'slot_duration_minutes' => ['sometimes', 'integer', 'min:30'],
        ];
    }
}
