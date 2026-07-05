<?php

namespace App\Rules;

use Closure;
use Illuminate\Contracts\Validation\DataAwareRule;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Support\Carbon;
use Illuminate\Translation\PotentiallyTranslatedString;

class MinimumSlotDuration implements DataAwareRule, ValidationRule
{
    /**
     * All of the data under validation.
     *
     * @var array<string, mixed>
     */
    protected array $data = [];

    /**
     * @param  mixed  $fallbackStartsAt  Used when starts_at is absent from the data under validation (e.g. a partial update that only sends ends_at).
     */
    public function __construct(
        protected readonly mixed $fallbackStartsAt = null,
        protected readonly mixed $fallbackEndsAt = null,
        protected readonly ?int $fallbackSlotDurationMinutes = null,
    ) {}

    /**
     * Run the validation rule.
     *
     * @param  Closure(string, ?string=): PotentiallyTranslatedString  $fail
     */
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        $startsAt = $this->data['starts_at'] ?? $this->fallbackStartsAt;
        $endsAt = $this->data['ends_at'] ?? $this->fallbackEndsAt;
        $slotDurationMinutes = $this->data['slot_duration_minutes'] ?? $this->fallbackSlotDurationMinutes;

        if (blank($startsAt) || blank($slotDurationMinutes)) {
            return;
        }

        $minimumEndsAt = Carbon::parse($startsAt)->addMinutes((int) $slotDurationMinutes);

        if (Carbon::parse($value)->lt($minimumEndsAt)) {
            $fail("The :attribute must be at least {$slotDurationMinutes} minutes after starts_at.");
        }
    }

    /**
     * Set the data under validation.
     *
     * @param  array<string, mixed>  $data
     */
    public function setData(array $data): static
    {
        $this->data = $data;

        return $this;
    }
}
