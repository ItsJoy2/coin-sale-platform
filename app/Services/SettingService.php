<?php

namespace App\Services;

use App\Models\Setting;
use Illuminate\Support\Facades\Cache;

class SettingService
{
    /**
     * Get setting value.
     */
    public function get(
        string $key,
        mixed $default = null
    ): mixed {

        return Cache::remember(
            'setting:' . $key,
            now()->addHours(24),
            function () use ($key, $default) {

                $setting = Setting::where(
                    'key',
                    $key
                )->first();

                if (!$setting) {
                    return $default;
                }

                return $setting->typed_value;
            }
        );
    }

    /**
     * Set setting value.
     */
    public function set(
        string $key,
        mixed $value,
        string $type = 'string',
        string $group = 'general',
        ?string $description = null,
        bool $isPublic = false
    ): Setting {

        $storedValue = $value;

        if ($type === 'json') {
            $storedValue = json_encode(
                $value,
                JSON_UNESCAPED_SLASHES
            );
        }

        $setting = Setting::updateOrCreate(
            [
                'key' => $key,
            ],
            [
                'value' => $storedValue,
                'type' => $type,
                'group' => $group,
                'description' => $description,
                'is_public' => $isPublic,
            ]
        );

        Cache::forget(
            'setting:' . $key
        );

        return $setting;
    }

    /**
     * Forget cached setting.
     */
    public function forget(string $key): void
    {
        Cache::forget(
            'setting:' . $key
        );
    }
}
