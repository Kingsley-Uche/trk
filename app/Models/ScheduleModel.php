<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ScheduleModel extends Model
{
    use HasFactory;

    // Define fillable fields to allow mass assignment
    protected $fillable = [
        'string',
        'vehicle_vin',
        'schedule_type',
        'date',
        'no_time',
        'no_kilometer',
        'no_hours',
        'category_time',
        'reminder_advance_days',
        'reminder_advance_km',
        'reminder_advance_hr',
        'start_date',
    ];

    // Define relationships if necessary
    public function vehicle()
    {
        return $this->belongsTo(Vehicle::class, 'vehicle_vin', 'vin');
    }

    public function driver()
    {
        return $this->belongsTo(Driver::class);
    }
}
