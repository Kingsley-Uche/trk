<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Vehicle extends Model
{
    use HasFactory;
   
    protected $fillable =['brand','model','year','vin','number_plate','type','user_id','vehicle_owner_id'];
    
    
   public function tracker()
{
    return $this->hasOne(Tracker::class)->select('device_id','protocol','ip','sim_no','params','port','network_protocol','vehicle_id');
}

    
    
      public function owner()
    {
        return $this->belongsTo(VehicleOwner::class, 'vehicle_owner_id');
    }
    
   // In App\Models\Vehicle.php

public function lastLocation()
{
    return $this->hasOne(LiveLocation::class, 'vehicle_id')
                ->latestOfMany();

}

 public function speedLimit()
{
    return $this->hasOne(SpeedModel::class, 'vehicle_vin', 'vin')->select('speed_limit', 'vehicle_vin');
}
}
