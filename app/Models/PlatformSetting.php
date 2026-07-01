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
        $value = Cache::rememberForever("setting_{$key}", function () use ($key) {
            return self::where('key', $key)->value('value');
        });

        if ($value !== null) {
            // we could cast it based on type, but for MVP returning value is okay, or we can fetch the model to cast it
            // Since we need it casted correctly, let's fetch the model.
            $model = Cache::rememberForever("setting_model_{$key}", function () use ($key) {
                return self::where('key', $key)->first();
            });
            
            if ($model) {
                return match ($model->type) {
                    SettingType::Integer => (int) $model->value,
                    SettingType::Boolean => filter_var($model->value, FILTER_VALIDATE_BOOLEAN),
                    SettingType::Json    => json_decode($model->value, true),
                    default              => (string) $model->value,
                };
            }
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
