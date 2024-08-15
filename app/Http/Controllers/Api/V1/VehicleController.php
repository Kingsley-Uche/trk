<?php

namespace App\Http\Controllers\Api\V1;

use Illuminate\Support\Facades\Validator;
use App\Http\Controllers\Controller;
use App\Models\Vehicle;
use App\Models\VehicleOwner;
use Illuminate\Http\Request;
use App\Models\User;
use Spatie\Permission\Models\Permission;
use Illuminate\Support\Facades\Auth;

class VehicleController extends Controller
{
    //
    public function Create(request $request){
            $user = Auth::guard('sanctum')->user();
        
            if (!$user || !in_array($user->user_type, ['admin', 'system_admin']) || !$user->can('create vehicle')) {
                return response()->json(['error' => 'Invalid Access'], 403);
            }
        
            // Validation rules
            $rules = [
                'make' => 'required|string|max:255',
                'model' => 'required|string|max:255',
                'year' => 'required|string|max:4',
                'vin' => 'required|string|max:17|unique:vehicles,vin', // VIN numbers have a maximum length of 17 characters
                'number_plate' => 'required|string|max:255|unique:vehicles,number_plate',
                'type' => 'required|string|max:255',
                'user_id' => 'required|exists:users,id', // Ensure the user_id exists in the users table
            ];
        
            // Custom validation messages (optional)
            $messages = [
                'make.required' => 'The vehicle make is required.',
                'model.required' => 'The model is required.',
                'year.required' => 'The year is required.',
                'vin.required' => 'The VIN is required.',
                'vin.max' => 'The VIN cannot be more than 17 characters.',
                'vin.unique' => 'The VIN must be unique.',
                'number_plate.required' => 'The registered plate number is required.',
                'number_plate.unique' => 'The registered plate number must be unique.',
                'type.required' => 'The type is required.',
                'user_id.required' => 'The user ID of the vehicle owner is required.',
                'user_id.exists' => 'The specified user ID does not exist.',
            ];
        
            // Validate the request
            $validator = Validator::make($request->all(), $rules, $messages);
        
            if ($validator->fails()) {
                return response()->json(['errors' => $validator->errors()], 422);
            }
        $user_car_owner = User::where('id',strip_tags($request->user_id))->first();
        
        $vehicle_owner = VehicleOwner::firstOrCreate(
            ['user_id' => $user_car_owner->id],
            [
                'first_name' => $user_car_owner->first_name,
                'last_name' => $user_car_owner->last_name,
                'email' => $user_car_owner->email,
                'phone' => $user_car_owner->phone,
            ]
        );
        
            // Create the vehicle record
            $vehicle = Vehicle::create([
                'brand' => $request->make,
                'model' => $request->model,
                'year' => $request->year,
                'vin' => $request->vin,
                'number_plate' => $request->number_plate,
                'type' => $request->type,
                'user_id' => $user_car_owner->id, // Assuming the vehicle_owner_id corresponds to a user_id
                'vehicle_owner_id'=>$vehicle_owner->id,
            ]);
           
            // Return a response indicating success
            return response()->json(['message' => 'Vehicle created successfully.', 'vehicle' => $vehicle], 201);
        }
        
        public function ViewAll()
        {
            // Only accessible to system admin, admin, individual, or organization
            $user = Auth::guard('sanctum')->user();
        
            if (!$user || !in_array($user->user_type, ['admin', 'system_admin', 'individual', 'organization'])) {
                return response()->json(['error' => 'Invalid Access'], 403);
            }
        
            if (in_array($user->user_type, ['admin', 'system_admin'])) {
                $vehicles = Vehicle::paginate(10);
            } elseif (in_array($user->user_type, ['individual', 'organization'])) {
                $vehicles = Vehicle::where('user_id', $user->id)->paginate(10);
            } else {
                return response()->json(['error' => 'Access not permitted for this user type'], 403);
            }
        
            return response()->json($vehicles);
        }

        public function modifyVehicle(Request $request)
        {
            $validator = Validator::make($request->all(), [
                'vehicle_id' => 'required|exists:vehicles,id',
                'user_id' => 'required|exists:users,id',
                'brand' => 'sometimes|string|max:255',
                'model' => 'sometimes|string|max:255',
                'year' => 'sometimes|string|max:4',
                'vin' => 'sometimes|string|max:17|unique:vehicles,vin,' . $request->vehicle_id,
                'number_plate' => 'sometimes|string|max:255|unique:vehicles,number_plate,' . $request->vehicle_id,
                'type' => 'sometimes|string|max:255',
            ]);
        
            if ($validator->fails()) {
                return response()->json(['errors' => $validator->errors()], 422);
            }
        
            $user = Auth::guard('sanctum')->user();
        
            if (!$user || !in_array($user->user_type, ['admin', 'system_admin'])) {
                return response()->json(['error' => 'Invalid Access'], 403);
            }
        
            if ($user->can('edit vehicle')) {
                $vehicle = Vehicle::where('id', strip_tags($request->vehicle_id))
                    ->where('user_id', strip_tags($request->user_id))
                    ->first();
        
                if (!$vehicle) {
                    return response()->json(['error' => 'Vehicle not found or access denied'], 404);
                }
        
                $updateData = array_filter($request->only(['brand', 'model', 'year', 'vin', 'number_plate', 'type']), function($value) {
                    return !is_null($value);
                });
        
                $vehicle->update($updateData);
                
                return response()->json(['message' => 'Vehicle updated successfully.', 'vehicle' => $vehicle]);
            }
        
            return response()->json(['error' => 'Unauthorized access'], 403);
        }
        
        
        public function DeleteVehicle(Request $request)
{
    $user = Auth::guard('sanctum')->user();

    if (!$user || !in_array($user->user_type, ['admin', 'system_admin'])) {
        return response()->json(['error' => 'Invalid Access'], 403);
    }
    
    if (!$user->can('delete vehicle')) {
        return response()->json(['error' => 'Invalid Access'], 403);
    }

    $validator = Validator::make($request->all(), [
        'vehicle_id' => 'required|exists:vehicles,id',
        'user_id' => 'required|exists:users,id',
    ]);

    if ($validator->fails()) {
        return response()->json(['errors' => $validator->errors()], 422);
    }

    $vehicle = Vehicle::where('id', strip_tags($request->vehicle_id))
        ->where('user_id', strip_tags($request->user_id))
        ->first();

    if (!$vehicle) {
        return response()->json(['error' => 'Vehicle not found or access denied'], 404);
    }

    $vehicle->delete();

    return response()->json(['message' => 'Vehicle deleted successfully', 'success' => true], 200);
}

    }

