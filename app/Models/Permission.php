<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Permission extends Model
{
    use HasFactory;

    protected $table = 'permissions';
    protected $primaryKey = 'id';  // Primary key column
    public $incrementing = true;   // Auto-incrementing primary key
    protected $keyType = 'int';    // Type of the primary key

    // Additional properties and methods as needed
}
