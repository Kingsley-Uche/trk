<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\UserController;
use App\Http\Controllers\Api\V1\AdminController;
use App\Http\Controllers\Api\V1\PermissionController;
use App\Http\Controllers\Api\V1\LiveTrackController;
use App\Http\Controllers\Api\V1\VehicleController;
use App\Http\Controllers\Api\V1\TrackerController;
use App\Http\Controllers\Api\V1\DriversController;
use App\Http\Controllers\Api\V1\SpeedController;
use App\Http\Controllers\Api\V1\ShareLocation;
use App\Http\Controllers\Api\V1\MaintenanceController;
use App\Http\Controllers\Api\V1\TripController;
use App\Http\Controllers\Api\DocumentController; // Ensure this is imported
use App\Http\Middleware\JsonResponse; // Ensure this is imported
use Illuminate\Support\Facades\Broadcast;
use App\Http\Controllers\Api\V1\DashCamController;

Broadcast::routes(['middleware' => ['auth:sanctum']]);

Route::get('/user', function (Request $request) {
    return $request->user();
})->middleware('auth:sanctum');

Route::post('/user/login', [UserController::class, 'login']);
Route::post('/user/register', [UserController::class, 'register']);
Route::post('user/verify/email', [UserController::class, 'confirmEmail']);
Route::post('user/regenerate/otp', [UserController::class, 'generateOtp']);
Route::post('user/change/password', [UserController::class, 'changePassword']);
Route::get('user/logout', [UserController::class, 'logout'])->middleware('auth:sanctum');
Route::get('/driver-documents/{filename}', [DocumentController::class, 'show'])->middleware('auth:sanctum');

Route::post('/initial/register', [UserController::class, 'registerAdmin']); // to be removed after admin registration
Route::post('/track/send', [LiveTrackController::class, 'updateLocation']);

Route::post('/send/location', [ShareLocation::class, 'sendLocation']);

// Apply JsonResponse and auth:api middleware to the following routes
Route::middleware([JsonResponse::class, 'auth:sanctum'])->group(function () {
    
    
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
    Route::post('/admin/vehicle/single', [VehicleController::class, 'getSingle']);
    Route::post('create/tracker', [TrackerController::class, 'saveTracker']);
    Route::post('view/all/tracker', [TrackerController::class, 'ViewAll']);
    Route::post('delete/tracker', [TrackerController::class, 'DeleteTracker']);
    Route::post('get/location', [LiveTrackController::class, 'getLocation']);
    Route::get('location/history', [LiveTrackController::class, 'get_location_history']);
    
    //dash cam
    Route::post('admin/register/dash/cam',[DashCamController::class, 'store']);
    
    //dash cam
    Route::post('admin/create/driver', [DriversController::class, 'createDriver']);
    Route::post('admin/update/driver', [DriversController::class, 'editDriver']);
    Route::post('admin/delete/driver', [DriversController::class, 'deleteDriver']);
    Route::get('admin/driver/all', [DriversController::class, 'getDrivers']);
    Route::get('admin/driver/single', [DriversController::class, 'getSingle']);
    Route::post('admin/create/speed/limit', [SpeedController::class, 'createSpeedLimit']);
    Route::post('admin/update/speed/limit', [SpeedController::class, 'createSpeedLimit']);
    Route::post('admin/delete/speed/limit', [SpeedController::class, 'destroy']);
    Route::post('schedule/service', [MaintenanceController::class, 'createSchedule']);

    Route::get('trips', [TripController::class, 'index']); // Get all trips
    Route::post('trips', [TripController::class, 'store']); // Create a new trip
    Route::get('trips/{id}', [TripController::class, 'show']); // Get a single trip
    Route::put('trips/{id}', [TripController::class, 'update']); // Update a trip
    Route::delete('trips/{id}', [TripController::class, 'destroy']);
});