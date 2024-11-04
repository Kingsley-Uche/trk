<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class LiveLocation extends Model
{
    use HasFactory;

    // Fillable attributes must match the database schema
    protected $fillable = [
        'vehicle_id',
        'tracker_id',
        'latitude',
        'longitude',
        'speed',
        'speed_unit', // Updated to match migration
        'course',
        'fix_time',
        'satellite_count', // Updated to match migration
        'active_satellite_count', // Updated to match migration
        'real_time_gps', // Updated to match migration
        'gps_positioned', // Updated to match migration
        'east_longitude', // Updated to match migration
        'north_latitude', // Updated to match migration
        'mcc',
        'mnc',
        'lac',
        'cell_id', // Updated to match migration
        'serial_number', // Updated to match migration
        'error_check', // Updated to match migration
        'event', // Updated to match migration
        'parse_time', // Updated to match migration
        'terminal_info',
        'voltage_level', // Updated to match migration
        'gsm_signal_strength', // Updated to match migration
        'response_msg', // Updated to match migration
        'status' // Added to match migration
    ];

    // Casts must match the data types defined in the migration
    protected $casts = [
        'fix_time' => 'datetime',
        'latitude' => 'decimal:7',
        'longitude' => 'decimal:7',
        'speed' => 'decimal:2',
        'speed_unit' => 'string',
        'course' => 'integer',
        'satellite_count' => 'integer',
        'active_satellite_count' => 'integer',
        'real_time_gps' => 'boolean',
        'gps_positioned' => 'boolean',
        'east_longitude' => 'boolean',
        'north_latitude' => 'boolean',
        'mcc' => 'integer',
        'mnc' => 'integer',
        'lac' => 'integer',
        'cell_id' => 'integer',
        'serial_number' => 'string',
        'error_check' => 'integer',
        'parse_time' => 'integer',
        'terminal_info' => 'json',
        'voltage_level' => 'string',
        'gsm_signal_strength' => 'string',
        'response_msg' => 'json',
        'status' => 'string',
    ];

    // Hide fields that should not be exposed in responses
    protected $hidden = ['id',];

    // Define relationship to Vehicle model
    public function vehicle()
    {
        return $this->belongsTo(Vehicle::class, 'vehicle_id');
    }

    // Define relationship to Tracker model
    public function tracker()
    {
        return $this->belongsTo(Tracker::class, 'tracker_id');
    }
}
