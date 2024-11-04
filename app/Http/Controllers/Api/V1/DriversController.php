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
            'country' => ['required', 'string'],
            'pin' => ['required', 'string'],
            'licence_number' => ['required', 'string'],
            'licence_issue_date' => ['required', 'date_format:d-m-Y'],
            'licence_expiry_date' => ['required', 'date_format:d-m-Y'],
            'phone' => ['required', 'string'],
            'guarantor_name' => ['required'],
            'guarantor_phone' => ['required'],
            'vehicle_id' => ['required', 'numeric'],
            'vehicle_vin' => ['required', 'string', 'exists:vehicles,vin'],
           'profile_picture' => ['required', 'file', 'mimes:jpg,jpeg,png,pdf', 'max:2048'], // 2MB limit
            'driving_licence' => ['required', 'file', 'mimes:jpg,jpeg,png,pdf', 'max:2048'], // 2MB limit
             'pin_doc' => ['required', 'file', 'mimes:jpg,jpeg,png,pdf', 'max:2048'], // 2MB limit
            'misc_doc' => ['sometimes', 'file', 'mimes:jpg,jpeg,png,pdf', 'max:2048'], 
        ]);
    
        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }
    
        // Convert date formats from d-m-Y to Y-m-d
        $licenceIssueDate = \Carbon\Carbon::createFromFormat('d-m-Y', $request->input('licence_issue_date'))->format('Y-m-d');
        $licenceExpiryDate = \Carbon\Carbon::createFromFormat('d-m-Y', $request->input('licence_expiry_date'))->format('Y-m-d');
    
        $data = $request->only([
            'name', 'email', 'phone', 'vehicle_vin', 'vehicle_id', 'pin', 'country', 'licence_number', 
            'guarantor_name', 'guarantor_phone'
        ]);
        
        // Add converted date values
        $data['licence_issue_date'] = $licenceIssueDate;
        $data['licence_expiry_date'] = $licenceExpiryDate;
    
        // Handle file uploads
        if ($request->hasFile('profile_picture')) {
            $data['profile_picture_path'] = $request->file('profile_picture')->storeAs(
                'drivers/' . $request->input('name'),
                $request->input('name') . '_profile_picture.' . $request->file('profile_picture')->getClientOriginalExtension()
            );
        }
        if ($request->hasFile('driving_licence')) {
            $data['driving_licence_path'] = $request->file('driving_licence')->storeAs(
                'drivers/' . $request->input('name'),
                $request->input('name') . '_driving_licence.' . $request->file('driving_licence')->getClientOriginalExtension()
            );
        }
        if ($request->hasFile('pin_doc')) {
            $data['pin_path'] = $request->file('pin_doc')->storeAs(
                'drivers/' . $request->input('name'),
                $request->input('name') . '_pin.' . $request->file('pin_doc')->getClientOriginalExtension()
            );
        }
        if ($request->hasFile('misc_doc')) {
            $data['miscellaneous_path'] = $request->file('misc_doc')->storeAs(
                'drivers/' . $request->input('name'),
                $request->input('name') . '_miscellaneous.' . $request->file('misc_doc')->getClientOriginalExtension()
            );
        }
    
        // Create the driver record
        $driver = Driver::create($data);
    
        return response()->json([
            'message' => 'Driver created successfully',
            'success' => true,
            'data' => $driver
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
            'vehicle_vin' => ['sometimes', 'string', 'exists:vehicles,vin'],
            'vehicle_id' => ['sometimes', 'numeric', 'exists:vehicles,id'],
            'driver_id' => ['required', 'numeric', 'exists:drivers,id'],
        ]);
    
        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }
    
        $driver = Driver::findOrFail($request->driver_id);
    
        // Only update the attributes that were provided
        $driver->update($request->only(['name', 'email', 'phone', 'vehicle_vin', 'vehicle_id']));
    
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
  public function getSingle(Request $request)
{
    // Get the authenticated user via Sanctum guard
    $user = Auth::guard('sanctum')->user();

    // Check if the user is authorized to perform this action
    if (!$this->isAuthorized($user)) {
        return response()->json(['error' => 'Access not permitted for this user type'], 403);
    }

    // Validate the incoming request
    $validator = Validator::make($request->all(), [
        'driver_id' => ['required', 'numeric', 'exists:drivers,id'],
    ]);

    // Check if validation fails
    if ($validator->fails()) {
        return response()->json(['errors' => $validator->errors()], 422);
    }

    // Retrieve the driver using the validated 'driver_id'
    $data = Driver::find($request->input('driver_id'));

    // Return the data as a JSON response
    return response()->json([
        'data' => $data,
        'success' => true
    ], 200);
}
}
