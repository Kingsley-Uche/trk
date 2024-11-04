<?php
namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\DashCam;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use App\Models\Vehicle;
use App\Models\howen_session;
use Spatie\Permission\Models\Permission;
use Illuminate\Support\Facades\Auth;
class DashCamController extends Controller
{
    // Fetch all DashCam records
    public function index()
    {
        $user = Auth::guard('sanctum')->user();

        if (!$user || !in_array($user->user_type, ['admin', 'system_admin']) || !$user->can('create device')) {
            return response()->json(['error' => 'Invalid Access'], 403); // Changed status code to 403 for unauthorized access
        }

        $dashCams = DashCam::all();
        return response()->json(['data' => $dashCams], 200);
    }

    // Create a new DashCam record
    public function store(Request $request)
    {
        $user = Auth::guard('sanctum')->user();

        if (!$user || !in_array($user->user_type, ['admin', 'system_admin']) || !$user->can('create device')) {
            return response()->json(['error' => 'Invalid Access'], 403); // Changed status code to 403 for unauthorized access
        }

        // Validate request data
        $request->validate([
            'deviceName' => 'required|string',
            'deviceID' => 'required|string',
            'deviceType' => 'required|string',
            'channelName' => 'required|array',
            'channelName.*'=>'required|string',
            'nodeGu' => 'required|string',
            'sim' => 'required|string',
            'vehicle_vin' => 'required|string',
        ]);

        
        $status = howen_session::first();
        if($status===null){
            
//create initial session for initial login
            
            
         $loginData = $this->login(['username' => 'admin', 'password' => md5('admin')]);
       
           howen_session::create(
                ['howen_session' => $loginData['data']['token'],// Store token from login data
                
                    'howen_pid' => $loginData['data']['pid'], // Store PID from login data
                    'updated_at' => now(),
                ]
            );

            $request->merge(['token' =>$loginData['data']['token']]);
        }else{
            $request->merge(['token' =>howen_session::first()->howen_session]);
        }
      // Debugging: Output all request data
      
        // Prepare data for local and external servers
        $data = $this->prepareDeviceData($request); $vehicle = Vehicle::where('vin', $data['vehicle_vin'])->first();
        if (!$vehicle) {
            return response()->json(['error' => 'Vehicle not found'], 404);
        }


        
        // Add the device to the external server
      $info = $this->handleExternalDeviceOperation('add', $data);

     
// Decode the JSON content into an array
$responseData = json_decode($info->getContent(), true);
      
     
       // Save the device locally
       

       $update = $this->saveDevice($data);
       
        return response()->json(['message' => $responseData['response']['msg'] ,'data' => $responseData['response']['data'], 'data_local'=>$update], 201);
    }


    // Fetch a single DashCam record by ID
public function show($id)
{

    $user = Auth::guard('sanctum')->user();

        if (!$user || !in_array($user->user_type, ['admin', 'system_admin']) || !$user->can('create device')) {
            return response()->json(['error' => 'Invalid Access'], 403); // Changed status code to 403 for unauthorized access
        }

    $dashCam = DashCam::find($id);
    
    if (!$dashCam) {
        return response()->json(['error' => 'DashCam not found'], 404);
    }

    return response()->json(['data' => $dashCam], 200);
}


// Delete a DashCam record by ID
public function destroy($id)
{

    $user = Auth::guard('sanctum')->user();

        if (!$user || !in_array($user->user_type, ['admin', 'system_admin']) || !$user->can('create device')) {
            return response()->json(['error' => 'Invalid Access'], 403); // Changed status code to 403 for unauthorized access
        }

    $dashCam = DashCam::find($id);

    if (!$dashCam) {
        return response()->json(['error' => 'DashCam not found'], 404);
    }
//delete from server
    $dashCam->delete();
//delete on app
    return response()->json(['message' => 'DashCam deleted successfully'], 200);
}

    
    private function handleExternalDeviceOperation($operation, $data)
    {
        $urlMap = [
            'add' => 'http://localhost:9966/vss/vehicle/apiAddDevice.action',
            'update' => 'http://localhost:9966/vss/vehicle/apiUpdateDevice.action',
            'remove' => 'http://localhost:9966/vss/vehicle/apiRemoveDevice.action',
        ];
    
        $url = $urlMap[$operation] ?? null;
        if (!$url) {
            return ['error' => true, 'message' => 'Invalid operation', 'status' => 400];
        }
    
        $old_token = $data['token'];
    
        // Attempt the operation
        $response = Http::withHeaders(['Content-Type' => 'application/json'])->post($url, $data)->json();
    
        // Handle expired session
        if (isset($response['status']) && $response['status'] === 10023) {
            // Perform login and retrieve new token
            $loginData = $this->login(['username' => 'admin', 'password' => md5('admin')]);
            $newToken = $loginData['data']['token'];
            $newPid = $loginData['data']['pid'];
    
            // Update data with new token and pid
            $data['token'] = $newToken;
            $data['pid'] = $newPid;
    
            // Retry the operation with the new session token
            $response = Http::withHeaders(['Content-Type' => 'application/json'])->post($url, $data)->json();
    
            // Update the session if the old session was found
            $status = howen_session::where('howen_session', $old_token)
                ->orderBy('id', 'desc')
                ->first();
    
            if ($status) {
                $status->update([
                    'howen_session' => $newToken,
                    'howen_pid' => $newPid,
                ]);
            }
    
            $response['valid_token'] = $newToken;
        }
    
        return response()->json(['response' => $response, 'success' => true], 200);
    }
    


    private function retryExternalOperation($url, $data)
    {
        $response = Http::withHeaders(['Content-Type' => 'application/json'])->post($url, json_encode($data))->json();
        
        
        if ($response['status']===10000) {

            return ['error' => false, 'response' => $response];
        }
     
        return ['error' => true, 'info' =>$response ];
    }

    // Login to the external server
    private function login($data)
    {
        
        $response = Http::withHeaders(['Content-Type' => 'application/json'])->post('http://localhost:9966/vss/user/apiLogin.action', $data);
        
      
        if ($response->successful()) {
            $contentType = $response->header('Content-Type');
            if (stripos($contentType, 'application/json') === 0) {
                return $response->json();  
            } else {
                return 'Error: Expected JSON response, but received: ' . $contentType;
            }
        }
        return $response->json();
    }

    // Update a DashCam record by ID
 
    // Delete a DashCam record by ID





    // Save device locally
    private function saveDevice($data)
    {
        // Perform the update or create operation
        $dashCam = DashCam::updateOrCreate(
            ['deviceID' => $data['deviceID']], // Unique identifier for the device
            [
                'deviceName' => $data['deviceName'],
                'deviceType' => $data['deviceType'],
                'channelName' => json_encode($data['channelName']),
                'nodeGu' => $data['nodeGu'],
                'sim' => $data['sim'],
                'vehicle_vin' => $data['vin'],
                'vehicle_id' => $data['vehicle_id'],
                'updated_at' => now(),
            ]
        );
    
        // Check if it was newly created or updated
        if ($dashCam->wasRecentlyCreated) {
            // Newly created
            $data = response()->json(['message' => 'DashCam created successfully'], 201);
        } else {
            // Updated
            $data = response()->json(['message' => 'DashCam updated successfully'], 200);
        }
      
    }
    

    // Prepare device data from request
    private function prepareDeviceData(Request $request)
    {
        // Using the session facade to access session data
        return [
            'deviceName' => $request->input('deviceName'),
            'deviceID' => $request->input('deviceID'),
            'deviceType' => $request->input('deviceType'),
            'channelName' => $request->input('channelName'),
            'nodeGu' => $request->input('nodeGu'),
            'sim' => $request->input('sim'),
            'vin' => $request->input('vehicle_vin'),
            'token' =>$request->input('token'),  // Get the first howen_session record and access the token
            'vehicle_id'=>'4590dfnmdfxhgj',
        ];
    }
    
    public function getAllDashCamsStatus()
{
    // Get the current session token
    $token = howen_session::first()->howen_session;
    
    // Prepare the request data
    $data = [
        'token' => $token,
        'pageNum' => -1, // Get all devices
        'pageCount' => -1,
    ];

    // Send the request to Howen API
    $response = Http::withHeaders(['Content-Type' => 'application/json'])
        ->post('http://localhost:9966/vss/vehicle/findAll.action', $data);

    // Decode the response
    $responseData = $response->json();

    // Return the response
    if ($responseData['status'] === 10000) {
        return response()->json(['data' => $responseData['data']['dataList']], 200);
    }

    return response()->json(['error' => $responseData['msg']], $responseData['status']);
}
public function updateDashCam(Request $request)
{
    // Validate request data
    $request->validate([
        'deviceID' => 'required|string',
        'deviceName' => 'required|string',
        'deviceType' => 'required|string',
        'channelName' => 'required|string',
    ]);

    // Get the current session token
    $token = howen_session::first()->howen_session;

    // Prepare data for the request
    $data = [
        'deviceID' => $request->input('deviceID'),
        'deviceName' => $request->input('deviceName'),
        'deviceType' => $request->input('deviceType'),
        'channelName' => $request->input('channelName'),
        'token' => $token,
    ];

    // Send the update request
    $response = Http::withHeaders(['Content-Type' => 'application/json'])
        ->post('http://localhost:9966/vss/vehicle/apiModifyDevice.action', $data);

    // Decode the response
    $responseData = $response->json();

    if ($responseData['status'] === 10000) {
        return response()->json(['message' => 'DashCam updated successfully'], 200);
    }

    return response()->json(['error' => $responseData['msg']], $responseData['status']);
}


public function deleteDashCam(Request $request)
{
    // Validate request data
    $request->validate([
        'deviceID' => 'required|string',
    ]);

    // Get the current session token
    $token = howen_session::first()->howen_session;

    // Prepare data for the request
    $data = [
        'deviceID' => $request->input('deviceID'),
        'token' => $token,
    ];

    // Send the delete request
    $response = Http::withHeaders(['Content-Type' => 'application/json'])
        ->post('http://localhost:9966/vss/vehicle/apiRemoveDevice.action', $data);

    // Decode the response
    $responseData = $response->json();

    if ($responseData['status'] === 10000) {
        return response()->json(['message' => 'DashCam deleted successfully'], 200);
    }

    return response()->json(['error' => $responseData['msg']], $responseData['status']);
}

}

