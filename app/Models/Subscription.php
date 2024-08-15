<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Subscription extends Model
{
    use HasFactory;
    protected $table ='subscriptions';
    protected $primaryKey = 'id';  // Primary key column
    public $incrementing = true;   // Auto-incrementing primary key
    protected $keyType = 'int';   
}
