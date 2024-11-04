<?php
namespace App\Events;

use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log; // Import the Log facade

class VehicleLocationUpdated implements ShouldBroadcast
{
    use Dispatchable, SerializesModels;

    public $vehicle;

    public function __construct($vehicle)
    {
        $this->vehicle = $vehicle;

        // Log the vehicle's location when the event is constructed
        // Log::info('Vehicle Location Updated', [
        //     'vehicle_owner_id' => $this->vehicle['vehicle_owner_id'],
        //     'location' => $this->vehicle['location'] ?? 'Location data not available', // Log location details
        //     'time' => now()->toDateTimeString(), // Log the current timestamp
        // ]);
    }

    // Broadcast to both admin/system_admin and the specific vehicle owner
    public function broadcastOn(): array
    {
        return [
            // Channel for admin and system admin users
            new PrivateChannel('admin.vehicle-location'),

            // Private channel for the specific vehicle owner
            new PrivateChannel('vehicle-owner.' . $this->vehicle['vehicle_owner_id']),
        ];
    }

    // Data to broadcast with the event
    public function broadcastWith(): array
    {
        return [
            'id' => $this->vehicle['vehicle_owner_id'],  // Vehicle owner ID
            'location_info' => $this->vehicle,  // Vehicle location information
        ];
    }
}
