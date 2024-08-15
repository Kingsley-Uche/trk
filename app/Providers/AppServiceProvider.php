<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use App\Events\Location;
use App\Listeners\LogBroadcast; // Import your listener class
use Illuminate\Support\Facades\Event;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        //
        Event::listen(
           Location::class,
            LogBroadcast::class
        );
    }
}
