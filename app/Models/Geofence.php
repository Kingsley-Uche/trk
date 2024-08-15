<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;
use MatanYadaev\EloquentSpatial\Objects\Point;
use MatanYadaev\EloquentSpatial\Objects\Polygon;
use MatanYadaev\EloquentSpatial\Traits\HasSpatial;


class Geofence extends Model
{
   
    use HasSpatial;
    

    protected $fillable = [
        'name',
        'created_by_user_id',
        'area',
        'vehicle_id'
    ];

    protected $casts = [
        //'location' => Point::class,
        'area' => Polygon::class,
    ];

    
}