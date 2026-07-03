<?php

namespace App\Enums;

use App\Enums\Concerns\HasValues;

enum AppointmentState: string
{
    use HasValues;

    case Pending = 'pending';
    case Confirmed = 'confirmed';
    case Completed = 'completed';
    case Cancelled = 'cancelled';
}
