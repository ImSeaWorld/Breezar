<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use App\Services\FlyApi;

class FlyApiServiceProvider extends ServiceProvider
{
    /**
     * Register services.
     */
    public function register(): void
    {
        $this->app->singleton(FlyApi::class, function ($app) {
            return new FlyApi();
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
