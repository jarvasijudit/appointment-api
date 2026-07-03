<?php

namespace App\Models;

use App\Enums\Specialization;
use App\Models\Concerns\HasUlidRouteBinding;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

#[Fillable(['name', 'email', 'specialization'])]
class Doctor extends Model
{
    use HasFactory, HasUlidRouteBinding, SoftDeletes;

    protected string $routeKeyName = 'ulid';

    protected function casts(): array
    {
        return [
            'specialization' => Specialization::class,
        ];
    }

    public function availabilities(): HasMany
    {
        return $this->hasMany(Availability::class);
    }

    public function appointments(): HasMany
    {
        return $this->hasMany(Appointment::class);
    }
}
