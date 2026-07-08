<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\SettingType;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Cache;

class PlatformSetting extends Model
{
    use HasFactory;

    protected $guarded = ['id', 'created_at', 'updated_at'];

    protected function casts(): array
    {
        return [
            'type' => SettingType::class,
        ];
    }

    /**
     * The ONLY way to read settings across the app.
     * Falls back to config/platform.php if not found in DB.
     */
    public static function get(string $key, mixed $default = null): mixed
    {
        $settingData = Cache::rememberForever("setting_data_v2_{$key}", function () use ($key) {
            $model = self::where('key', $key)->first();
            return $model ? ['value' => $model->value, 'type' => $model->type] : null;
        });

        if ($settingData) {
            return match ($settingData['type']) {
                SettingType::Integer => (int) $settingData['value'],
                SettingType::Boolean => filter_var($settingData['value'], FILTER_VALIDATE_BOOLEAN),
                SettingType::Json    => json_decode($settingData['value'], true),
                default              => (string) $settingData['value'],
            };
        }

        return config("platform.{$key}", $default);
    }

    /**
     * Set a setting value.
     */
    public static function set(string $key, mixed $value, SettingType $type = SettingType::String): void
    {
        $stringValue = match ($type) {
            SettingType::Json => json_encode($value),
            SettingType::Boolean => $value ? '1' : '0',
            default => (string) $value,
        };

        self::updateOrCreate(
            ['key' => $key],
            ['value' => $stringValue, 'type' => $type]
        );

        Cache::forget("setting_{$key}");
        Cache::forget("setting_model_{$key}");
    }
}
