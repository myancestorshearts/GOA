<?php

namespace App\Providers;
use App;
use App\Services;

use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     *
     * @return void
     */
    public function register()
    {
        //
        App::bind('restapi', function() {
            return new Services\RestApi;
        });
        
        App::bind('formatter', function() {
            return new Services\Formatter;
        });
        
        App::bind('apiauth', function() {
            return new Services\ApiAuth;
        });
    }

    /**
     * Bootstrap any application services.
     *
     * @return void
     */
    public function boot()
    {
        //
    }
}
