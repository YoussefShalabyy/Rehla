<?php

declare(strict_types=1);

use App\Enums\SettingType;
use App\Models\PlatformSetting;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

it('gets and sets settings correctly via static methods', function () {
    // Set
    PlatformSetting::set('test_int', 42, SettingType::Integer);
    PlatformSetting::set('test_bool', true, SettingType::Boolean);
    PlatformSetting::set('test_json', ['a' => 1], SettingType::Json);
    PlatformSetting::set('test_str', 'hello', SettingType::String);

    // Get
    expect(PlatformSetting::get('test_int'))->toBe(42)
        ->and(PlatformSetting::get('test_bool'))->toBeTrue()
        ->and(PlatformSetting::get('test_json'))->toBe(['a' => 1])
        ->and(PlatformSetting::get('test_str'))->toBe('hello');
});

it('falls back to config if setting missing in db', function () {
    config(['platform.test_fallback' => 'fallback_value']);

    expect(PlatformSetting::get('test_fallback'))->toBe('fallback_value');
});
