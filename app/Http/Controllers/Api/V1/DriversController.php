<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;
use App\Models\Drivers as Driver;
use App\Models\Vehicle;

class DriversController extends Controller
{
    // Create a new driver
    public function createDriver(Request $request)
    {
        $user = Auth::guard('sanctum')->user();

        if (!$this->isAuthorized($user)) {
            return response()->json(['error' => 'Access not permitted for this user type'], 403);
        }

        $validator = Validator::make($request->all(), [
            'name' => ['required', 'string'],
            'email' => ['required', 'email'],
            'phone' => ['required', 'string'],
            'vehicle_vin' => ['required', 'string', 'exists:vehicles,vin'],
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $data = Driver::create($this->sanitizeInput($request, ['name', 'email', 'phone', 'vehicle_vin']));

        return response()->json([
            'message' => 'Driver created successfully',
            'success' => true,
            'data' => $data
        ], 200);
    }

    // Edit an existing driver
    public function editDriver(Request $request)
    {
        $user = Auth::guard('sanctum')->user();

        if (!$this->isAuthorized($user)) {
            return response()->json(['error' => 'Access not permitted for this user type'], 403);
        }

        $validator = Validator::make($request->all(), [
            'name' => ['sometimes', 'string'],
            'email' => ['sometimes', 'email'],
            'phone' => ['sometimes', 'regex:/^([0-9\s\-\+\(\)]*)$/'],
            'vehicle_vin' => ['required', 'string', 'exists:vehicles,vin'],
            'vehicle_id' => ['required', 'numeric', 'exists:vehicles,id'],
            'driver_id' => ['required', 'numeric', 'exists:drivers,id'],
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $driver = Driver::findOrFail($request->driver_id);
        $driver->update($this->sanitizeInput($request, ['name', 'email', 'phone', 'vehicle_vin']));

        return response()->json([
            'message' => 'Driver updated successfully',
            'success' => true,
            'data' => $driver
        ], 200);
    }

    // Delete a driver
    public function deleteDriver(Request $request)
    {
        $user = Auth::guard('sanctum')->user();

        if (!$this->isAuthorized($user)) {
            return response()->json(['error' => 'Access not permitted for this user type'], 403);
        }

        $validator = Validator::make($request->all(), [
            'driver_id' => ['required', 'numeric', 'exists:drivers,id'],
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        Driver::destroy($request->driver_id);

        return response()->json([
            'message' => 'Driver deleted successfully',
            'success' => true
        ], 200);
    }

    // Helper method to check if the user is authorized
    private function isAuthorized($user)
    {
        return in_array($user->user_type, ['admin', 'system_admin']);
    }

    // Helper method to sanitize input
    private function sanitizeInput(Request $request, array $fields)
    {
        $sanitized = [];
        foreach ($fields as $field) {
            $sanitized[$field] = strip_tags($request->input($field));
        }
        return $sanitized;
    }

    public function getDrivers(Request $request)
    {
        $user = Auth::guard('sanctum')->user();
    
        if (!$this->isAuthorized($user)) {
            return response()->json(['error' => 'Access not permitted for this user type'], 403);
        }
    
        // Retrieve paginated data
        $data = Driver::paginate(10);
    
        return response()->json([
            'success' => true,
            'data' => $data
        ], 200);
    }
    
}
