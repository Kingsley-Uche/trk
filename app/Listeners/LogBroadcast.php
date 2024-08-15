<?php

namespace App\Listeners;

use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;
use App\Events\Location;
use Illuminate\Support\Facades\Log;
class LogBroadcast
{
    /**
     * Create the event listener.
     */
    public function __construct()
    {
        //
    }

    /**
     * Handle the event.
     */
    public function handle(Location $event): void
    {
        //
        
          Log::info('Broadcasted event:', ['event' => $event]);
          
    }
}
