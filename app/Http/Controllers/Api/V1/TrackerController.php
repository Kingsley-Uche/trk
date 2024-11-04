<?php 
namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Tracker;
use App\Models\Vehicle;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;
use Spatie\Permission\Models\Permission;
use App\Models\Track_Vehicle;
use App\Models\User;
use App\Http\Controllers\Api\V1\Gps;
class TrackerController extends Controller
{
    public function saveTracker(Request $request)
    {
        // Ensure the user is authenticated and authorized
        $user = Auth::guard('sanctum')->user();

        if (!$user || !in_array($user->user_type, ['admin', 'system_admin']) || !$user->can('create device')) {
            return response()->json(['error' => 'Invalid Access'], 403); // Changed status code to 403 for unauthorized access
        }

        // Validation rules
        $rules = [
            'protocol' => 'required|string',
            'ip' => 'required|string|max:255', // Updated to string validation instead of email
            'sim_no' => ['required', 'regex:/^([0-9\s\-\+\(\)]*)$/', 'max:20'], // Added max length validation
            'imei' => 'required|string|max:255',
            'port' => 'required|string|max:20',
            'network_protocol' => 'required|string|max:50',
            'vehicle_vin' => 'required|exists:vehicles,vin'
        ];

        // Custom validation messages (optional)
        $messages = [
            'protocol.required' => 'The device protocol is required.',
            'ip.required' => 'The device ip address is required.',
            'sim_no.required' => 'The SIM number is required.',
            'imei.required' => 'IMEI is required.',
            'port.required' => 'Port is required.',
            'network_protocol.required' => 'Network protocol is required.',
            'vehicle_vin.required' => 'Provide the VIN of the vehicle.',
            'vehicle_vin.exists' => 'The provided VIN does not exist in the vehicles table.'
        ];



$name = 'Chrysler';
$uniqueId = '358657103707134';
$phone = '+234808136458934';
$model = 'sebring';
$category = 'default';
 $this->saveDevice($name, $uniqueId, $phone, $model, $category);
 die();
        // Validate the request
        $validator = Validator::make($request->all(), $rules, $messages);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $vehicle = Vehicle::where('vin', strip_tags($request->input('vehicle_vin')))->first();
        
        if (!$vehicle) {
            return response()->json(['error' => 'Vehicle not found'], 404);
        }

        // Sanitize input and save
        $tracker = Tracker::create([
            'device_id' => filter_var($request->input('imei'), FILTER_SANITIZE_STRING),
            'protocol' => filter_var($request->input('protocol'), FILTER_SANITIZE_STRING),
            'ip' => filter_var($request->input('ip'), FILTER_SANITIZE_STRING),
            'sim_no' => filter_var($request->input('sim_no'), FILTER_SANITIZE_STRING),
            'params' => json_encode($request->input('params') ?: null), // Assuming params is an array
            'port' => filter_var($request->input('port'), FILTER_SANITIZE_STRING),
            'network_protocol' => filter_var($request->input('network_protocol'), FILTER_SANITIZE_STRING),
            'vehicle_vin' => filter_var($request->input('vehicle_vin'), FILTER_SANITIZE_STRING),
            'vehicle_id'=>$vehicle->id,
        ]);

        Track_Vehicle::create([
            'tracker_id' => $tracker->id,
            'track_vehicle_id' => $vehicle->id,
            'vehicle_vin' => $tracker->vehicle_vin,
            'tracker_imei' => $tracker->device_id
        ]);

        return response()->json(['message' => 'Tracker saved successfully'], 201);
    }
    public function viewAll()
    {
        $trackers = Tracker::paginate(10);
        return response()->json($trackers);
    }
    

    public function deleteTracker(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'tracker_id' => 'required|exists:track_devices,id'
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        Tracker::where('id', strip_tags($request->input('tracker_id')))->delete();
        return response()->json(['message' => 'Tracker deleted successfully', 'success' => true], 200);
    }
    
    private function saveDevice($name, $uniqueId, $phone, $model, $category){
        $attributes = new \stdClass();
        var_dump($attributes);
        $info = Gps::deviceAdd($name, $uniqueId, $phone, $model, $category, $attributes);
        var_dump($info);
        die();
    }
    private function DestroyDevice($id){
        Gps::deviceDelete(int($id));
        
    }
}
