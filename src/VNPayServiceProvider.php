<?php

namespace VNPayPayment;

use Illuminate\Support\ServiceProvider;
use VNPayPayment\Console\Commands\VNPayStatusCommand;
use VNPayPayment\Console\Commands\VNPayTestCommand;

class VNPayServiceProvider extends ServiceProvider
{
    /**
     * Register services.
     *
     * @return void
     */
    public function register(): void
    {
        // Merge config
        $this->mergeConfigFrom(
            __DIR__ . '/../config/vnpay.php',
            'vnpay'
        );

        // Register VNPayClient as singleton
        $this->app->singleton('vnpay', function ($app) {
            return new VNPayClient(config('vnpay'));
        });

        // Register alias
        $this->app->alias('vnpay', VNPayClient::class);
    }

    /**
     * Bootstrap services.
     *
     * @return void
     */
    public function boot(): void
    {
        // Publish config
        if ($this->app->runningInConsole()) {
            $this->publishes([
                __DIR__ . '/../config/vnpay.php' => config_path('vnpay.php'),
            ], 'vnpay-config');

            // Register commands
            $this->commands([
                VNPayStatusCommand::class,
                VNPayTestCommand::class,
            ]);
        }

        // Load routes if exists
        $this->loadRoutesFrom(__DIR__ . '/../routes/vnpay.php');
    }

    /**
     * Get the services provided by the provider.
     *
     * @return array
     */
    public function provides(): array
    {
        return ['vnpay', VNPayClient::class];
    }
}