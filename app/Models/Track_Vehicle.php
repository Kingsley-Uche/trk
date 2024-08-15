<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Track_Vehicle extends Model
{
    use HasFactory;
    protected $fillable =['tracker_id','track_vehicle_id', 'vehicle_vin', 'tracker_imei'];
}
