<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Drivers extends Model
{
    use HasFactory;
    protected $fillable = [
        'name',
        'email',
        'phone',
        'vehicle_vin',
        'vehicle_id',
        'pin',
        'country',
        'licence_number',
        'licence_issue_date',
        'licence_expiry_date',
        'guarantor_name',
        'guarantor_phone',
        'profile_picture_path',
        'driving_licence_path',
        'pin_path',
        'miscellaneous_path',
    ];

    // Cast date attributes to Carbon instances
    protected $dates = [
        'licence_issue_date',
        'licence_expiry_date',
    ];
}
