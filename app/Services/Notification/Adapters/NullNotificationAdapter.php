<?php

declare(strict_types=1);

namespace App\Services\Notification\Adapters;

use App\Interfaces\PushNotificationInterface;
use Illuminate\Support\Facades\Log;

/**
 * NullNotificationAdapter — used in tests and local development.
 * Logs notifications instead of sending them to Expo/FCM.
 * Never calls any external push provider.
 */
final class NullNotificationAdapter implements PushNotificationInterface
{
    public function send(string $token, string $title, string $body, array $data = []): void
    {
        Log::info('[NullNotificationAdapter] Push notification sent', [
            'token' => $token,
            'title' => $title,
            'body'  => $body,
            'data'  => $data,
        ]);
    }

    public function sendBulk(array $tokens, string $title, string $body, array $data = []): void
    {
        Log::info('[NullNotificationAdapter] Bulk push notification sent', [
            'token_count' => count($tokens),
            'title'       => $title,
            'body'        => $body,
        ]);
    }
}
