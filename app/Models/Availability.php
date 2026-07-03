<?php

namespace App\Models;

use App\Models\Concerns\HasUlidRouteBinding;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable(['doctor_id', 'starts_at', 'ends_at', 'slot_duration_minutes'])]
class Availability extends Model
{
    use HasFactory, HasUlidRouteBinding;

    protected string $routeKeyName = 'ulid';

    protected function casts(): array
    {
        return [
            'starts_at' => 'datetime',
            'ends_at' => 'datetime',
        ];
    }

    public function doctor(): BelongsTo
    {
        return $this->belongsTo(Doctor::class);
    }
}
