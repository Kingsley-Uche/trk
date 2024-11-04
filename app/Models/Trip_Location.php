<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Trip_Location extends Model
{
    use HasFactory;

    // Define the table name if it does not follow Laravel's pluralization convention
    protected $table = 'trip_locations';

    // Define the fillable attributes
    protected $fillable = [
        'trip_id',
        'start_location',
        'start_lon',
        'start_lat',
        'end_location',
        'end_lat',
        'end_lon',
        'departure_time',
        'arrival_time',
    ];

 protected $dates = ['departure_time', 'arrival_time'];
    // Define relationships
    public function trip()
    {
        return $this->belongsTo(Trip::class);
    }
}
