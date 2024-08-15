<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\UserController;
use App\Http\Controllers\Api\V1\AdminController;
use App\Http\Controllers\Api\V1\PermissionController;
use App\Http\Controllers\Api\V1\LiveTrackController;
use App\Http\Controllers\Api\V1\VehicleController;
use App\Http\Controllers\Api\V1\TrackerController;


Route::get('/user', function (Request $request) {
    return $request->user();
})->middleware('auth:sanctum');

Route::post('/user/login', [UserController::class, 'login']);
Route::post('/user/register', [UserController::class, 'register']);
Route::post('user/verify/email', [UserController::class, 'confirmEmail']);
Route::post('user/regenerate/otp', [UserController::class, 'generateOtp']);
Route::post('user/change/password', [UserController::class, 'changePassword']);
Route::get('user/logout',[UserController::class, 'logout'])->middleware('auth:sanctum');
Route::post('/initial/register', [UserController::class, 'registerAdmin']);//to be removed after admin registration
Route::post('/track/send', [LiveTrackController::class, 'updateLocation']);

Route::middleware('auth:sanctum')->group(function ()  {
Route::post('/admin/create/user', [AdminController::class, 'RegisterUser']);
Route::post('/admin/create/permission', [PermissionController::class, 'InitiateCreate']);
Route::post('/admin/assign/permission', [PermissionController::class, 'assignPermission']);
Route::get('/get/all/users', [AdminController::class, 'GetAll']);
Route::post('add/geofence', [LiveTrackController::class, 'addGeofence']);
Route::post('check/geofence', [LiveTrackController::class, 'CheckGeofence']);
Route::post('create/vehicle', [VehicleController::class, 'Create']);
Route::post('modify/vehicle', [VehicleController::class, 'ModifyVehicle']);
Route::post('view/vehicle', [VehicleController::class, 'ViewAll']);
Route::post('delete/vehicle', [VehicleController::class, 'DeleteVehicle']);
Route::post('create/tracker',[TrackerController::class,'saveTracker']);
Route::post('view/all/tracker',[TrackerController::class,'ViewAll']);
Route::post('delete/tracker',[TrackerController::class,'DeleteTracker']);
Route::post('get/location', [LiveTrackController::class, 'getLocation']);


});
