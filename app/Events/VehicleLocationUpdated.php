<?php

namespace App\Events;

use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PresenceChannel;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;

use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;


class VehicleLocationUpdated implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;


    public $vehicle;

    public function __construct($vehicle)
    {
        $this->vehicle = $vehicle;
       // \Log::info('VehicleLocationUpdated event created', ['vehicle' => $vehicle]);
    }

    public function broadcastOn(): array
    {
        return [
            new Channel('vehicle-location'),
        ];
    }

    public function broadcastWith()
    {
        return [
            'id' => $this->vehicle['id'],
           // 'location' => $this->vehicle->location,
        ];
    }

}
