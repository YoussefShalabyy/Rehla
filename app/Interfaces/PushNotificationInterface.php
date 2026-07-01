<?php

declare(strict_types=1);

namespace App\Interfaces;

interface PushNotificationInterface
{
    /**
     * Send a push notification to a single device token.
     *
     * @param  array<string, mixed>  $data  Extra payload passed to the device
     */
    public function send(string $token, string $title, string $body, array $data = []): void;

    /**
     * Send a push notification to multiple device tokens.
     *
     * @param  string[]              $tokens
     * @param  array<string, mixed>  $data
     */
    public function sendBulk(array $tokens, string $title, string $body, array $data = []): void;
}
