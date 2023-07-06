<?php

namespace App\Providers;

use Illuminate\Support\Facades\View;
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
    }

    /**
     * Bootstrap any application services.
     *
     * @return void
     */
    public function boot()
    {
        View::share([
            'client_id' => config('eideasy.client_id'),
            'card_domain' => config('eideasy.card_domain'),
            'api_url' => config('eideasy.api_url'),
        ]);
    }
}
