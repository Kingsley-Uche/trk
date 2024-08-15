<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class LiveLocation extends Model
{
    use HasFactory;
    protected $fillable = [
        'vehicle_id', 
        'tracker_id', 
        'latitude', 
        'longitude', 
        'speed', 
        'course', 
        'fix_time', 
        'terminal_info'
    ];

    protected $casts = [
        'fix_time' => 'datetime',
        'terminal_info' => 'json',
    ];
    
protected $hidden =['id','vehicle_id','tracker_id'];
    public function vehicle()
    {
        return $this->belongsTo(Vehicle::class, 'vehicle_id');
    }


    public function tracker()
    {
        return $this->belongsTo(Tracker::class, 'tracker_id');
    }
}
