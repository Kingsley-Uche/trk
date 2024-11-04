<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class VehicleStatus extends Model
{
    use HasFactory;
    protected $fillable =['vehicle_vin', 'vehicle_id', 'vehicle_status','device_id'];
}
