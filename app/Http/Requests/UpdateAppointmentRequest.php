<?php

namespace App\Http\Requests;

use App\Models\Appointment;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class UpdateAppointmentRequest extends FormRequest
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
        /** @var Appointment $appointment */
        $appointment = $this->route('appointment');

        $startsAt = $this->input('starts_at', $appointment->starts_at);

        return [
            'doctor_id' => ['sometimes', 'ulid', 'exists:doctors,ulid'],
            'starts_at' => ['sometimes', 'date', 'after:now'],
            'ends_at' => ['sometimes', 'date', "after:{$startsAt}"],
        ];
    }
}
