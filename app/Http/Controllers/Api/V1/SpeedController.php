<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;
use App\Models\Driver;
use App\Models\Vehicle;
use App\Models\SpeedModel;

class SpeedController extends Controller
{
    /**
     * Create or update a speed limit for a vehicle.
     */
    public function createSpeedLimit(Request $request)
    {
        $user = Auth::guard('sanctum')->user();

        if (!$this->isAuthorized($user)) {
            return response()->json(['error' => 'Access not permitted for this user type'], 403);
        }

        // Validation of the request input
        $validator = Validator::make($request->all(), [
            'speed_limit' => ['required', 'numeric'],
            'vehicle_vin' => ['required', 'string', 'exists:vehicles,vin'],
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        // Sanitize input data
        $data = $this->sanitizeInput($request, ['speed_limit', 'vehicle_vin']);

        // Create or update speed limit
        $response = $this->createOrUpdate($data);

        return response()->json(['message' => 'Speed limit set successfully', 'data' => $response], 200);
    }

    /**
     * Check if the user is authorized.
     */
    private function isAuthorized($user)
    {
        return in_array($user->user_type, ['admin', 'system_admin']);
    }

    /**
     * Sanitize input data.
     */
    private function sanitizeInput(Request $request, array $fields)
    {
        $sanitized = [];
        foreach ($fields as $field) {
            $sanitized[$field] = strip_tags($request->input($field));
        }
        return $sanitized;
    }

    /**
     * Create or update the speed model record.
     */
    private function createOrUpdate(array $data)
    {
        // Use updateOrCreate to create a new record or update an existing one
        $response = SpeedModel::updateOrCreate(
            ['vehicle_vin' => $data['vehicle_vin']], // Condition for update
            ['speed_limit' => $data['speed_limit']] // Data to update or create
        );

        return $response;
    }
   

public function destroy(Request $request)
{
    // Retrieve the authenticated user
    $user = Auth::guard('sanctum')->user();

    if (!$this->isAuthorized($user)) {
        return response()->json(['error' => 'Access not permitted for this user type'], 403);
    }

    // Validation of the request input
    $validator = Validator::make($request->all(), [
        'vehicle_vin' => ['required', 'string', 'exists:vehicles,vin'],
    ]);

    if ($validator->fails()) {
        return response()->json(['errors' => $validator->errors()], 422);
    }

    // Sanitize input data
    $data = $this->sanitizeInput($request, ['vehicle_vin']);

    // Find the SpeedModel by vehicle_vin and delete it
    $speedModel = SpeedModel::where('vehicle_vin', $data['vehicle_vin'])->first();

    if ($speedModel) {
        $speedModel->delete();
        return response()->json(['success' => true, 'message' => 'Speed limit alert deactivated'], 200);
    }

    // If no matching SpeedModel found
    return response()->json(['error' => 'Speed limit record not found'], 404);
}

}
