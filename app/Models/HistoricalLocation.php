<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class HistoricalLocation extends Model
{
    use HasFactory;

    // Specify the table associated with the model
    protected $table = 'historical_locations';

    // Specify the attributes that are mass assignable
    protected $fillable = [
        'vehicle_id',
        'tracker_id',
        'latitude',
        'longitude',
        'speed',
        'speed_unit',
        'course',
        'fix_time',
        'satellite_count',
        'active_satellite_count',
        'real_time_gps',
        'gps_positioned',
        'east_longitude',
        'north_latitude',
        'mcc',
        'mnc',
        'lac',
        'cell_id',
        'serial_number',
        'error_check',
        'event',
        'parse_time',
        'terminal_info',
        'voltage_level',
        'gsm_signal_strength',
        'response_msg',
        'status'
    ];

    // Specify the attributes that should be cast to native types
    protected $casts = [
        'event' => 'array',
        'terminal_info' => 'array',
        'response_msg' => 'array',
        'fix_time' => 'datetime',
    ];

    // Define any relationships if necessary
    public function vehicle()
    {
        return $this->belongsTo(Vehicle::class);
    }

    public function tracker()
    {
        return $this->belongsTo(Tracker::class);
    }
}
