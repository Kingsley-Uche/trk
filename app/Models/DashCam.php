<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class DashCam extends Model
{
    use HasFactory;

    protected $fillable = [
        'deviceName',       // For storing the device name
        'deviceID',         // For storing the device ID
        'deviceType',       // For storing the device type
        'channelName',      // For storing the channel name
        'nodeGu',           // For storing nodeGu
        'sim',              // For storing SIM number
        'vehicle_id',       // For storing the vehicle ID (foreign key)
        'vehicle_vin',      // For storing the vehicle VIN (foreign key)
    ];

    // Hide these attributes when converting the model to JSON
    protected $hidden = ['created_at', 'updated_at'];

    /**
     * Relationship with the Vehicle model.
     * This assumes that `vehicle_vin` references the `vin` column in the `vehicles` table.
     */
    public function vehicle()
    {
        return $this->belongsTo(Vehicle::class, 'vehicle_vin', 'vin');
    }

    /**
     * You could also add another relationship for `vehicle_id` if needed.
     */
    public function vehicleById()
    {
        return $this->belongsTo(Vehicle::class, 'vehicle_id');
    }
}
