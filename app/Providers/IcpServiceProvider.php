<?php

namespace App\Providers;

use App\Services\Icp\IcpClient;
use Illuminate\Support\ServiceProvider;

class IcpServiceProvider extends ServiceProvider
{
    /**
     * Register services.
     */
    public function register(): void
    {
           $this->app->singleton(IcpClient::class, fn () => new IcpClient());
    }

    /**
     * Bootstrap services.
     */
    public function boot(): void
    {
        //
    }
}
