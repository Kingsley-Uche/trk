<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Events\VehicleLocationUpdated;
use Illuminate\Support\Facades\Validator;
use App\Models\Tracker;
use App\Models\Geofence as GeoFence;
use App\Models\Vehicle;
use App\Models\LiveLocation;
use App\Models\HistoricalLocation;
use App\Models\User;
use App\Models\Drivers as Driver;
use Illuminate\Support\Facades\Auth;
use MatanYadaev\EloquentSpatial\Objects\Polygon;
use MatanYadaev\EloquentSpatial\Objects\LineString;
use MatanYadaev\EloquentSpatial\Objects\Point;
use MatanYadaev\EloquentSpatial\Enums\Srid;
use Illuminate\Support\Facades\Broadcast;

class LiveTrackController extends Controller
{
  public function updateLocation(Request $request)
{
      $data = json_decode($request->getContent(), true);
    // Decode JSON payload if necessary (typically not required in Laravel)
  $validator = Validator::make($data, [
        'expectsResponse' => 'required|boolean',
        'fixTime' => 'required|date',
        'satCnt' => 'required|integer',
        'satCntActive' => 'required|integer',
        'lat' => 'required|numeric',
        'lon' => 'required|numeric',
        'speed' => 'required|numeric',
        'speedUnit' => 'required|string|in:km/h,mph',
        'realTimeGps' => 'required|boolean',
        'gpsPositioned' => 'required|boolean',
        'eastLongitude' => 'required|boolean',
        'northLatitude' => 'required|boolean',
        'course' => 'required|integer',
        'mcc' => 'required|integer',
        'mnc' => 'required|integer',
        'lac' => 'required|integer',
        'cellId' => 'required|integer',
        'serialNr' => 'required|integer',
        'errorCheck' => 'required|integer',
        'event' => 'required|array',
        'event.number' => 'required|integer',
        'event.string' => 'required|string',
        'parseTime' => 'required|integer',
        'terminalInfo' => 'sometimes|required|array',
        'terminalInfo.status' => 'sometimes|required|boolean',
        'terminalInfo.ignition' => 'sometimes|required|boolean',
        'terminalInfo.charging' => 'sometimes|required|boolean',
        'terminalInfo.alarmType' => 'sometimes|required|string|in:normal,alarm',
        'terminalInfo.gpsTracking' => 'sometimes|required|boolean',
        'terminalInfo.relayState' => 'sometimes|required|boolean',
        'voltageLevel' => 'sometimes|required|string|in:very high,high,medium,low,very low',
        'gsmSigStrength' => 'sometimes|required|string|in:good signal,medium signal,weak signal,no signal',
        'responseMsg' => 'sometimes|required|array',
        'responseMsg.type' => 'sometimes|required|string|in:Buffer',
        'responseMsg.data' => 'sometimes|required|array',
        'responseMsg.data.*' => 'sometimes|required|integer',
        'imei' => 'sometimes|required|numeric',
        'serialNumber' => 'sometimes|required|string',
    ]);

    if ($validator->fails()) {
        return response()->json(['errors' => $validator->errors()], 422);
    }

    $tracker = Tracker::where('device_id', strip_tags($data['imei']))->first();
    if (!$tracker) {
        return response()->json(['message' => 'Tracker not found'], 404);
    }
    //
   
   $vehicle = $tracker->vehicle()->with('owner')->first();
   if (!$vehicle) {
        return response()->json(['message' => 'Vehicle not found'], 404);
    }
  
  
  $vehicleWithOwner = $vehicle->load('owner')->toArray();
  
   $latitude = strip_tags($data['lat']);
    $longitude = strip_tags($data['lon']);
     $vehicleWithOwner['tracker'] =$data;
     $vehicleWithOwner['within_geofence']=$this->checkInGeofence($vehicleWithOwner['id'], $latitude, $longitude);

        
         broadcast(new \App\Events\Location($vehicleWithOwner));
    $lastLocation = LiveLocation::where('vehicle_id', $vehicle['id'])->where('tracker_id', $tracker->id)->latest()->first();
    if ($lastLocation && ($lastLocation->latitude == $data['lat'] && $lastLocation->longitude == $data['lon'])) {
        return response()->json(['message' => 'Location has not changed'], 200);
    }

    // Update live location
    $liveLocation = LiveLocation::updateOrCreate(
        ['vehicle_id' => $vehicle['id'], 'tracker_id' => $tracker->id],
        [
            'latitude' => $data['lat'],
            'longitude' => $data['lon'],
            'speed' => $data['speed'],
            'course' => $data['course'],
            'fix_time' => $data['fixTime'],
            'terminal_info' => $data['terminalInfo'] ?? null,
        ]
    );

    // Move old live location to historical data if it exists and is older than 1 minute
    if ($lastLocation) {
        HistoricalLocation::create([
            'vehicle_id' => $lastLocation->car_id,
            'tracker_id' => $lastLocation->tracker_id,
            'latitude' => $lastLocation->latitude,
            'longitude' => $lastLocation->longitude,
            'speed' => $lastLocation->speed,
            'course' => $lastLocation->course,
            'fix_time' => $lastLocation->fix_time,
            'terminal_info' => $lastLocation->terminal_info,
        ]);
    }

  
    return response()->json(['message' => 'Location updated successfully'], 200);
}

    private function geofencing($userId, $vehicleId, $southwest, $northeast,$zone)
    {
        $result['formatted_address'] = $zone;

    $srid = 4326; // Spatial Reference System Identifier

    $points = [
        new Point($southwest['lat'], $southwest['lng'], $srid),
        new Point($northeast['lat'], $southwest['lng'], $srid),
        new Point($northeast['lat'], $northeast['lng'], $srid),
        new Point($southwest['lat'], $northeast['lng'], $srid),
        new Point($southwest['lat'], $southwest['lng'], $srid) // Closing the polygon
    ];

    // Define the polygon with the correct SRID
    $area = new Polygon([new LineString($points, $srid)]);

    GeoFence::updateOrCreate(
        ['vehicle_id' => $vehicleId],
        ['name' => $zone, 'created_by_user_id' => $userId, 'area' => $area]
    );

  

    }

    
public function addGeofence(Request $request)
{
 $validator = Validator::make($request->all(), [
    'location' => ['required', 'array'],
    'location.southwest.lat' => ['required', 'numeric'],
    'location.southwest.lng' => ['required', 'numeric'],
    'location.northeast' => ['required', 'array'],
    'location.northeast.lat' => ['required', 'numeric'],
    'location.northeast.lng' => ['required', 'numeric'],
    'vin' => ['required', 'string', 'max:17', 'exists:vehicles,vin'],
    'zone' => ['required', 'string']
]);

    if ($validator->fails()) {
        return response()->json(['errors' => $validator->errors()], 422);
    }
    

    $user = Auth::guard('sanctum')->user();
    $userId = $user->id;
    $vehicleId= vehicle::where('vin', strip_tags($request->vin))->first()->id;

    
    if (!$user || !in_array($user->user_type, ['admin', 'system_admin']) || !$user->can('create vehicle')) {
            return response()->json(['error' => 'Invalid Access'], 422);
        }
    $southwest = $request->input('location.southwest');
    $northeast = $request->input('location.northeast');
    $zone = strip_tags($request->zone);
    

    $this->geofencing($userId, $vehicleId, $southwest, $northeast, $zone);
    return response()->json(['success' => true, 'message' => 'Geofencing activated'], 201);
}


private function checkInGeofence($vehicleId, $latitude, $longitude)
{
    $location = new Point($latitude, $longitude, 4326);
    
    // Retrieve the geofence for the specified vehicle
    $geofence = GeoFence::where('vehicle_id', $vehicleId)->first();

    // Check if the geofence exists
    if (!$geofence) {
        return null; // Return null if no geofence is found
    }

    // Extract polygon coordinates and zone name
    $polygonCoordinates = $this->extractPolygonCoordinates($geofence->area);
    $circle = $this->convertPolygonToCircle($polygonCoordinates);
    $zone = $geofence->name;

    // Check if the location is within the geofence area
    $isInGeofence = $geofence->whereContains('area', $location)->first() !== null;

    // Construct the geofence array
    $geofenceData = [
        'coordinates' => $polygonCoordinates,
        'zone' => $zone,
        'is_in_geofence' => $isInGeofence,
        'circle_data'=>$circle,
    ];

    return $geofenceData; // Return the geofence data array
}
   public function checkGeofence(Request $request)
{
    // Validate the incoming request
    $validator = Validator::make($request->all(), [
        'latitude_' => ['required', 'numeric'],
        'longitude' => ['required', 'numeric'],
        'vin' => ['required', 'string', 'exists:vehicles,vin']
    ]);

    // If validation fails, return a 422 response with errors
    if ($validator->fails()) {
        return response()->json(['errors' => $validator->errors()], 422);
    }

    // Retrieve validated data
    $vin = strip_tags($request->input('vin'));
    $latitude = strip_tags($request->input('latitude_'));
    $longitude = strip_tags($request->input('longitude'));
    $vehicleId= vehicle::where('vin', $vin)->first()->id;

    // Check if the coordinates are within the geofence
    $isInGeofence = $this->checkInGeofence($vehicleId, $latitude, $longitude);

    // Return the result
    return response()->json(['success' => true, 'in_geofence' => $isInGeofence], 200);
}
public function getLocation()
{
    $user = Auth::guard('sanctum')->user();
    if (!$user) {
        return response()->json(['error' => 'Invalid Access'], 422);
    }

    $vehicle = Vehicle::where('user_id', $user->id)->first();
    if (!$vehicle) {
        return response()->json(['message' => 'Vehicle not found'], 404);
    }

    // Load the owner, tracker, and lastLocation relationships
    $vehicleWithRelations = $vehicle->load('owner', 'tracker', 'lastLocation');

    // Ensure the tracker is loaded and available
    if (!$vehicleWithRelations->tracker) {
        return response()->json(['message' => 'Tracker not found'], 404);
    }

    // Retrieve the latest location for the tracker
    $lastLocation = $vehicleWithRelations->lastLocation;
 
    if (!$lastLocation) {
        return response()->json(['message' => 'Location not found'], 404);
    }

    $result = [
        'vehicle' => $vehicleWithRelations->toArray(),
    ];
$result['vehicle']['last_location']['latitude']=floatval($result['vehicle']['last_location']['latitude']);
$result['vehicle']['last_location']['longitude']=floatval($result['vehicle']['last_location']['longitude']);
$result['vehicle']['last_location']['speed']=floatval($result['vehicle']['last_location']['speed']);
$result['vehicle']['geofence']=$this-> checkInGeofence($vehicle->id, $result['vehicle']['last_location']['latitude'], $result['vehicle']['last_location']['longitude']);
$result['vehicle']['last_location']['driver'] = Driver::where('vehicle_vin', $vehicle->vin)->first() ?? null;
    return response()->json($result);
}

private function extractPolygonCoordinates($area)
{ $points = $area->getCoordinates(); // Adjust this based on your implementation
    $coordinates = [];

    foreach ($points[0] as $point) {
        $coordinates[] = [
            'lng' => $point[0],
            'lat' => $point[1],
        ];
    }

    return $coordinates;
}


private function convertPolygonToCircle(array $coordinates): array
{
    $latSum = 0;
    $lngSum = 0;
    $maxDistance = 0;
    $count = count($coordinates);

    // Calculate centroid
    foreach ($coordinates as $coord) {
        $latSum += $coord['lat'];
        $lngSum += $coord['lng'];
    }

    $centroid = [
        'lat' => $latSum / $count,
        'lng' => $lngSum / $count
    ];

    // Calculate maximum distance from centroid to any vertex
    foreach ($coordinates as $coord) {
        $distance = $this->calculateDistance($centroid, $coord);
        if ($distance > $maxDistance) {
            $maxDistance = $distance;
        }
    }

    return [
        'center' => $centroid,
        'radius' => $maxDistance
    ];
}

private function calculateDistance(array $point1, array $point2): float
{
    $earthRadius = 6371000; // Earth radius in meters
    $latFrom = deg2rad($point1['lat']);
    $lonFrom = deg2rad($point1['lng']);
    $latTo = deg2rad($point2['lat']);
    $lonTo = deg2rad($point2['lng']);

    $latDelta = $latTo - $latFrom;
    $lonDelta = $lonTo - $lonFrom;

    $a = sin($latDelta / 2) ** 2 + cos($latFrom) * cos($latTo) * sin($lonDelta / 2) ** 2;
    $c = 2 * atan2(sqrt($a), sqrt(1 - $a));

    return $earthRadius * $c;
}



}