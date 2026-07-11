<?php

declare(strict_types=1);

namespace App\Providers;

use App\Exceptions\BaseException;
use App\Interfaces\MediaStorageInterface;
use App\Interfaces\PaymentGatewayInterface;
use App\Interfaces\PushNotificationInterface;
use App\Services\Media\Adapters\LocalMediaAdapter;
use App\Services\Notification\Adapters\NullNotificationAdapter;
use App\Services\Payment\Adapters\NullPaymentAdapter;
use Illuminate\Support\Facades\Log;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\ServiceProvider;
use Illuminate\Database\Eloquent\Relations\Relation;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     * Bind interfaces to their concrete adapters based on config.
     */
    public function register(): void
    {
        $this->bindPaymentGateway();
        $this->bindMediaStorage();
        $this->bindPushNotification();
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        Relation::enforceMorphMap([
            'listing' => 'App\Models\Listing',
            'user'    => 'App\Models\User',
        ]);

        RateLimiter::for('api', function (Request $request) {
            return Limit::perMinute(60)->by($request->user()?->id ?: $request->ip());
        });

        RateLimiter::for('auth', function (Request $request) {
            return Limit::perMinute(5)->by($request->ip());
        });

        RateLimiter::for('payment', function (Request $request) {
            return Limit::perHour(10)->by($request->user()?->id ?: $request->ip());
        });

        RateLimiter::for('webhook', function (Request $request) {
            return Limit::perMinute(100)->by($request->ip());
        });
    }

    private function bindPaymentGateway(): void
    {
        $gateway = config('payment.default', 'null_adapter');

        $this->app->bind(PaymentGatewayInterface::class, match ($gateway) {
            'paymob' => \App\Services\Payment\Adapters\PaymobAdapter::class,
            default  => NullPaymentAdapter::class,
        });
    }

    private function bindMediaStorage(): void
    {
        $provider = config('media.default', 'local');

        $this->app->bind(MediaStorageInterface::class, match ($provider) {
            'cloudinary' => \App\Services\Media\Adapters\CloudinaryAdapter::class,
            default      => LocalMediaAdapter::class,
        });
    }

    private function bindPushNotification(): void
    {
        $provider = config('notification.default', 'null');

        $this->app->bind(PushNotificationInterface::class, match ($provider) {
            'expo' => \App\Services\Notification\Adapters\ExpoAdapter::class,
            default => NullNotificationAdapter::class,
        });
    }
}
