<?php

namespace VNPayPayment;

use Illuminate\Routing\Router;
use Illuminate\Support\ServiceProvider;
use VNPayPayment\Console\Commands\VNPayStatusCommand;
use VNPayPayment\Console\Commands\VNPayTestCommand;
use VNPayPayment\Http\Middleware\VerifyVNPayIpnIp;

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
        // Register middleware alias
        $router = $this->app->make(Router::class);
        $router->aliasMiddleware('vnpay.ipn', VerifyVNPayIpnIp::class);

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