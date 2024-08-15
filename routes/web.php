<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\V1\liveTrackController;
use App\Http\Controllers\V1\TrackIt;
Route::post('/', [TrackIt::class, 'updateLocation']);
Route::get('/', function(){
     return view('welcome');
});