<?php

namespace App\Repositories;

use App\Models\Setting;

class SettingsRepository
{
    public function get(string $key, mixed $default = null): mixed
    {
        $row = Setting::query()->where('key', $key)->first();
        if ($row !== null) {
            return $row->value ?? $default;
        }
        return $default;
    }

    public function set(string $key, mixed $value): void
    {
        Setting::query()->updateOrCreate(
            ['key' => $key],
            ['value' => is_scalar($value) ? (string) $value : json_encode($value)]
        );
    }
}
