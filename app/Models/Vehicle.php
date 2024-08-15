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
    return $this->hasOne(Tracker::class);
}

    
    
      public function owner()
    {
        return $this->belongsTo(VehicleOwner::class, 'vehicle_owner_id');
    }
    
   // In App\Models\Vehicle.php

public function lastLocation()
{
    return $this->hasOne(LiveLocation::class, 'vehicle_id')->latestOfMany();
}

    
}
