<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Trip extends Model
{
    use HasFactory;

    // // Specify the primary key if it's not 'id'
    // protected $primaryKey = 'id'; // Uncomment if trip_id is the primary key
    // public $incrementing = false; // Uncomment if trip_id is not an auto-incrementing integer

    protected $fillable = [
        'trip_id',
        'name',
        'driver_id',
        'vehicle_vin',
        'description',
    ];

    // Correctly hide the 'id' attribute

    // Define relationships if needed
    public function driver()
    {
        return $this->belongsTo(Driver::class, 'driver_id');
    }

    public function vehicle()
    {
        return $this->belongsTo(Vehicle::class, 'vehicle_vin', 'vin');
    }
    
        public function tripLocations()
    {
        return $this->hasMany(Trip_Location::class, 'trip_id');
    }

}