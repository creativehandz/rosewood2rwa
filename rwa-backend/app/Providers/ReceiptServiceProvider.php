<?php

namespace App\Providers;

use App\Services\PaymentService;
use App\Services\ReceiptService;
use Illuminate\Support\ServiceProvider;

class ReceiptServiceProvider extends ServiceProvider
{
    /**
     * Register services.
     */
    public function register(): void
    {
        $this->app->singleton(ReceiptService::class, function ($app) {
            return new ReceiptService();
        });

        $this->app->singleton(PaymentService::class, function ($app) {
            return new PaymentService($app->make(ReceiptService::class));
        });
    }

    /**
     * Bootstrap services.
     */
    public function boot(): void
    {
        //
    }
}
