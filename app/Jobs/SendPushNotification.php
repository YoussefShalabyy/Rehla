<?php

declare(strict_types=1);

namespace App\Jobs;

use App\Interfaces\PushNotificationInterface;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Throwable;

class SendPushNotification implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function __construct(
        public array|string $tokens,
        public string $title,
        public string $body,
        public array $data = []
    ) {}

    public function handle(PushNotificationInterface $push): void
    {
        $tokens = is_array($this->tokens) ? $this->tokens : [$this->tokens];

        if (empty($tokens)) {
            return;
        }

        try {
            $push->sendBulk($tokens, $this->title, $this->body, $this->data);
        } catch (Throwable $e) {
            // Isolation Rule: Log failures, never rethrow
            Log::warning('SendPushNotification Job failed: ' . $e->getMessage(), [
                'tokens' => $tokens,
                'title'  => $this->title,
            ]);
        }
    }
}
