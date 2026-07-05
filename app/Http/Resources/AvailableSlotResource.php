<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Carbon;

/**
 * @property array{starts_at: Carbon, ends_at: Carbon} $resource
 */
class AvailableSlotResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'starts_at' => $this['starts_at']->toIso8601String(),
            'ends_at' => $this['ends_at']->toIso8601String(),
        ];
    }
}
