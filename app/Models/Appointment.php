<?php

namespace App\Models;

use App\Enums\AppointmentState;
use App\Models\Concerns\HasUlidRouteBinding;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable(['patient_id', 'doctor_id', 'starts_at', 'ends_at', 'state', 'cancellation_reason'])]
class Appointment extends Model
{
    use HasFactory, HasUlidRouteBinding;

    protected string $routeKeyName = 'ulid';

    protected function casts(): array
    {
        return [
            'starts_at' => 'datetime',
            'ends_at' => 'datetime',
            'state' => AppointmentState::class,
        ];
    }

    public function patient(): BelongsTo
    {
        return $this->belongsTo(Patient::class);
    }

    public function doctor(): BelongsTo
    {
        return $this->belongsTo(Doctor::class);
    }
}
