<?php

declare(strict_types=1);

namespace App\Services\Admin;

use Illuminate\Support\Facades\DB;
use InvalidArgumentException;

class PlatformSettingsService
{
    public function all(): array
    {
        return DB::table('platform_settings')->pluck('value', 'key')->toArray();
    }

    public function update(string $key, mixed $value): array
    {
        $exists = DB::table('platform_settings')->where('key', $key)->exists();

        if (!$exists) {
            throw new InvalidArgumentException("Setting key '{$key}' does not exist.");
        }

        DB::table('platform_settings')->where('key', $key)->update([
            'value'      => is_array($value) ? json_encode($value) : $value,
            'updated_at' => now(),
        ]);

        return [$key => $value];
    }
}
