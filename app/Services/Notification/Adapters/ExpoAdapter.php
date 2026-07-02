<?php

declare(strict_types=1);

namespace App\Services\Notification\Adapters;

use App\Interfaces\PushNotificationInterface;
use ExpoSDK\Expo;
use ExpoSDK\ExpoMessage;
use Throwable;
use Illuminate\Support\Facades\Log;

class ExpoAdapter implements PushNotificationInterface
{
    public function send(string $token, string $title, string $body, array $data = []): void
    {
        $this->sendBulk([$token], $title, $body, $data);
    }

    public function sendBulk(array $tokens, string $title, string $body, array $data = []): void
    {
        try {
            $message = (new ExpoMessage([
                'title' => $title,
                'body'  => $body,
                'data'  => $data,
            ]))->to($tokens);

            $expo = Expo::driver('file'); // Or simply new Expo() 
            // the ctwillie/expo-server-sdk-php wrapper handles the access token via env or config

            $response = $expo->send($message);
            
            // We can log response for debugging if needed
            Log::debug('Expo push response', ['response' => $response]);
            
        } catch (Throwable $e) {
            Log::warning('ExpoAdapter: Failed to send push notification', [
                'error'  => $e->getMessage(),
                'tokens' => $tokens,
            ]);
            // Re-throw so the Job can catch it, or just swallow it here.
            // The isolation rule says the Job must catch it, so we can rethrow or let the job handle it.
            throw $e;
        }
    }
}
