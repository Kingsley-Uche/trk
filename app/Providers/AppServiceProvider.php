<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use App\Events\Location;
use App\Listeners\LogBroadcast; // Import your listener class
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\DB; // Import the DB facade

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
        // Register the event listener
        Event::listen(
            Location::class,
            LogBroadcast::class
        );

        // Set the timezone for the MySQL connection if the database is connected
        if (DB::connection()->getPdo()) {
            DB::statement("SET time_zone = '+01:00'");
        }
    }
}
