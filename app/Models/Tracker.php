<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Tracker extends Model
{
    protected $fillable = [
        'device_id', 
        'protocol', 
        'ip', 
        'sim_no',
        'params', 
        'port',
        'network_protocol',
        'vehicle_id',
        'vehicle_vin',
        'events'
    ];

    protected $hidden = ['id'];

    protected $casts = [
        'params' => 'array',
        'events' => 'array',
    ];

    public function vehicle()
    {
        return $this->belongsTo(Vehicle::class, 'vehicle_vin', 'vin');
    }
}

