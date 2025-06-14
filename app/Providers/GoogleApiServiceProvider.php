<?php

namespace App\Providers;

use App\Services\GoogleApiService;
use Illuminate\Support\ServiceProvider;

class GoogleApiServiceProvider extends ServiceProvider
{
    public function register()
    {
        $this->app->singleton(GoogleApiService::class, function ($app) {
            return new GoogleApiService(
                config('google-api.analytics'),
                config('google-api.ads'),
                config('google-api.search_console')
            );
        });
    }

    public function boot()
    {
        //
    }
}