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

    /**
     * @return array<int, self>
     */
    public function allowedTransitions(): array
    {
        return match ($this) {
            self::Pending => [self::Confirmed, self::Cancelled],
            self::Confirmed => [self::Completed, self::Cancelled],
            self::Completed, self::Cancelled => [],
        };
    }

    public function canTransitionTo(self $state): bool
    {
        return in_array($state, $this->allowedTransitions(), true);
    }
}
