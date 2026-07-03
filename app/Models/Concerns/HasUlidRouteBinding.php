<?php

namespace App\Models\Concerns;

use Illuminate\Support\Str;

trait HasUlidRouteBinding
{
    public function getRouteKeyName(): string
    {
        return 'ulid';
    }

    protected static function bootHasUlidRouteBinding(): void
    {
        static::creating(function (self $model) {
            if (empty($model->ulid)) {
                $model->ulid = (string) Str::ulid();
            }
        });
    }
}
