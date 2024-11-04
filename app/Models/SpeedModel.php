<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class SpeedModel extends Model
{
    use HasFactory;

    protected $fillable = ['vehicle_vin', 'speed_limit'];


    // Optionally hide the other attributes if you only want to show speed_limit_value
   protected $hidden = [ 'created_at', 'updated_at','vehicle_vin'];

    public function vehicle()
    {
        return $this->belongsTo(Vehicle::class, 'vehicle_vin', 'vin');
    }
}
