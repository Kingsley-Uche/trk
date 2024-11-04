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
use App\Models\SpeedModel;
use Illuminate\Support\Facades\Auth;
use MatanYadaev\EloquentSpatial\Objects\Polygon;
use MatanYadaev\EloquentSpatial\Objects\LineString;
use MatanYadaev\EloquentSpatial\Objects\Point;
use MatanYadaev\EloquentSpatial\Enums\Srid;
use Illuminate\Support\Facades\Broadcast;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use App\Models\VehicleStatus;
use Carbon\Carbon;

use Illuminate\Support\Arr;
class LiveTrackController extends Controller
{




public function updateLocation(Request $request)
{
    $data = $request->all();


    // Validate incoming data
    $validator = Validator::make($data, [
        'tracker_data' => 'required|array',
        'tracker_data.*.uniqueId' => 'required|string',
        'tracker_data.*.status' => 'required|string|in:online,offline',
        'tracker_data.*.lastUpdate' => 'required|date',
        'tracker_data.*.positionId' => 'required|integer',
        'tracker_data.*.position.latitude' => 'required|numeric',
        'tracker_data.*.position.longitude' => 'required|numeric',
        'tracker_data.*.position.speed' => 'required|numeric',
        'tracker_data.*.position.course' => 'required|integer',
        'tracker_data.*.position.altitude' => 'required|numeric',
        'tracker_data.*.position.distance' => 'required|numeric',
        'tracker_data.*.position.total_distance' => 'required|numeric',
        'tracker_data.*.position.motion' => 'sometimes',
        'tracker_data.*.position.sat' => 'sometimes|numeric',
        'tracker_data.*.position.fix_time' => 'sometimes|string',
        'tracker_data.*.position.battery_level' => 'sometimes|numeric',
        'tracker_data.*.position.terminal_info' => 'sometimes|',
        'tracker_data.*.position.parse_time' => 'sometimes|string',
        'tracker_data.*.position.gsm_rssi' => 'sometimes|numeric',
        'tracker_data.*.position.ignition' => 'sometimes',
    ]);

    if ($validator->fails()) {
        return response()->json(['errors' => $validator->errors()], 422);
    }

    $distanceThreshold = 10; // Distance threshold in meters

    foreach ($data['tracker_data'] as $trackerInfo) {
        if (empty($trackerInfo)) {
            continue;
        }

        $position = $trackerInfo['position'];
        $position['terminal_info'] = json_decode($position['terminal_info'], true);
        $gps_positioned = isset($position['sat']);

        $tracker = Tracker::with('vehicle.owner', 'vehicle.speedLimit')
            ->where('device_id', strip_tags($trackerInfo['uniqueId']))
            ->first();

        if (!$tracker || !$tracker->vehicle) {
            \Log::info('Tracker or vehicle not found:', $trackerInfo);
            continue; // Skip if tracker or vehicle is not found
        }

        $vehicle = $tracker->vehicle;
        $vehicle_status = $this->determineVehicleStatus($position);

        // Retrieve last location from cache
        $lastLocation = $this->getLastLocation($vehicle, $tracker);

        // Prepare live location data
        $liveLocationData = $this->prepareLiveLocationData($position, $gps_positioned);

        if ($this->shouldUpdateLocation($lastLocation, $position, $distanceThreshold)) {
            // Update or create live location
            LiveLocation::updateOrCreate(
                ['vehicle_id' => $vehicle->id, 'tracker_id' => $tracker->id],
                $liveLocationData
            );

            // Save historical location only if it's far enough from the last one
            if ($this->shouldUpdateHistoricalLocation($lastLocation, $position, $distanceThreshold)) {
                $this->saveHistoricalLocation($vehicle->id, $tracker->id, $liveLocationData);
            }

            // Broadcast updated location
            $this->broadcastUpdatedLocation($vehicle, $trackerInfo, $vehicle_status, $position);
        }

        // Update vehicle status
        $this->updateVehicleStatus($this->prepareVehicleStatusData($trackerInfo, $vehicle, $vehicle_status));
    }

    return response()->json(['message' => 'Location updated successfully'], 200);
}

// Check if the new position is far enough from the last historical location
private function shouldUpdateHistoricalLocation($lastLocation, $newPosition, $distanceThreshold)
{
    if (!$lastLocation) {
        return true; // If there's no last location, we can save the new one
    }

    // Calculate distance to last historical location
    $distance = $this->haversineGreatCircleDistance(
        $lastLocation->latitude,
        $lastLocation->longitude,
        $newPosition['latitude'],
        $newPosition['longitude']
    );

    return $distance >= $distanceThreshold; // Return true if the distance exceeds the threshold
}



private function shouldUpdateLocation($lastLocation, $position, $distanceThreshold)
{
    if ($lastLocation) {
        $distance = $this->haversineGreatCircleDistance(
            $lastLocation->latitude, 
            $lastLocation->longitude, 
            $position['latitude'], 
            $position['longitude']
        );
        return $distance >= $distanceThreshold; // Update only if the distance exceeds the threshold
    }
    return true; // If no last location, we should update
}

private function getLastLocation($vehicle, $tracker)
{
    return Cache::remember("last_location_{$vehicle->id}_{$tracker->id}", 60, function() use ($vehicle, $tracker) {
        return LiveLocation::where('vehicle_id', $vehicle->id)
            ->where('tracker_id', $tracker->id)
            ->latest('updated_at')
            ->first();
    });
}

private function determineVehicleStatus($position)
{
    if (isset($position['ignition'])) {
        $ignition = $position['ignition'];
        return $ignition ? ($position['speed'] > 0 ? 'Moving' : 'Idling') : 'Parked';
    }
    return 'offline';
}



private function prepareLiveLocationData($position, $gps_positioned)
{

    // Safely access cellTowers array
    $cellTowers = Arr::get($position, 'terminal_info.cellTowers', []);

    return [
        'latitude' => $position['latitude'],
        'longitude' => $position['longitude'],
        'speed' => $position['speed'],
        'course' => $position['course'],
        'fix_time' => $position['fix_time'] ?? '',
        'satellite_count' => $position['sat'] ?? 0,
        'gps_positioned' => $gps_positioned,
        'voltage_level' => $position['battery_level'] ?? null,
        'gsm_signal_strength' => $position['gsm_rssi'] ?? null,
        'terminal_info' => json_encode($position['terminal_info'] ?? []),
        'status' => $position['motion'] ?? null,
        'real_time_gps' => true,
        'east_longitude' => 1,
        'north_latitude' => 1,
        'mcc' => Arr::get($cellTowers, '0.mobileCountryCode', 0),
        'mnc' => Arr::get($cellTowers, '0.mobileNetworkCode', 0),
        'active_satellite_count' => $position['sat'] ?? 0,
        'updated_at' => isset($position['fix_time']) ? Carbon::parse($position['fix_time'])->format('Y-m-d H:i:s') : now(),
        'lac' => Arr::get($cellTowers, '0.locationAreaCode', 0),
        'cell_id' => Arr::get($cellTowers, '0.cellId', 0),
        'serial_number'=>'string',
        'parse_time'=>45,
        'error_check'=>$position['error_check']??0,
        'event'=>$position['event']??[]
    ];
}

private function broadcastUpdatedLocation($vehicle, $trackerInfo, $vehicle_status, $position)
{
    $vehicleWithOwner = $vehicle->load('owner')->toArray();
    $vehicleWithOwner['speed_limit'] = $vehicle->speedLimit->speed_limit ?? null;
    $vehicleWithOwner['tracker'] = $trackerInfo;
    $vehicleWithOwner['within_geofence'] = $this->checkInGeofence($vehicle->id, $position['latitude'], $position['longitude']);
    $vehicleWithOwner['vehicle_status'] = $vehicle_status;
    

    broadcast(new \App\Events\VehicleLocationUpdated($vehicleWithOwner));
    //broadcast(new \App\Events\Location($vehicleWithOwner));
}

private function prepareVehicleStatusData($trackerInfo, $vehicle, $vehicle_status)
{
    return [
        'device_id' => $trackerInfo['uniqueId'],
        'vehicle_vin' => $vehicle->vin,
        'vehicle_id' => $vehicle->id,
        'vehicle_status' => $vehicle_status,
    ];
}

// Helper function to calculate distance between two GPS coordinates
private function haversineGreatCircleDistance($latitudeFrom, $longitudeFrom, $latitudeTo, $longitudeTo, $earthRadius = 6371000)
{
    // Convert from degrees to radians
    $latFrom = deg2rad($latitudeFrom);
    $lonFrom = deg2rad($longitudeFrom);
    $latTo = deg2rad($latitudeTo);
    $lonTo = deg2rad($longitudeTo);

    // Haversine formula
    $latDelta = $latTo - $latFrom;
    $lonDelta = $lonTo - $lonFrom;

    $a = sin($latDelta / 2) * sin($latDelta / 2) +
         cos($latFrom) * cos($latTo) *
         sin($lonDelta / 2) * sin($lonDelta / 2);
    $c = 2 * atan2(sqrt($a), sqrt(1 - $a));

    return $earthRadius * $c; // Returns distance in meters
}

// Save historical location
private function saveHistoricalLocation($vehicleId, $trackerId, $liveLocationData)
{
    // Ensure you include gps_positioned in the data being saved
    HistoricalLocation::create([
        'vehicle_id' => $vehicleId,
        'tracker_id' => $trackerId,
        'latitude' => $liveLocationData['latitude'],
        'longitude' => $liveLocationData['longitude'],
        'speed' => $liveLocationData['speed'],
        'course' => $liveLocationData['course'],
        'fix_time' => $liveLocationData['fix_time'],
        'satellite_count' => $liveLocationData['satellite_count'],
        'voltage_level' => $liveLocationData['voltage_level'],
        'gsm_signal_strength' => $liveLocationData['gsm_signal_strength'],
        'terminal_info' => $liveLocationData['terminal_info'],
        'status' => $liveLocationData['status'],
        'real_time_gps' => $liveLocationData['real_time_gps'],
        'active_satellite_count' => $liveLocationData['active_satellite_count'],
        'gps_positioned' => $liveLocationData['gps_positioned'], // Add this line
        'east_longitude'=>$liveLocationData['east_longitude'],
        'north_latitude'=>$liveLocationData['north_latitude'],
        'updated_at' =>$liveLocationData['fix_time'],
        'mcc'=>$liveLocationData['mcc'],
        'mnc'=>$liveLocationData['mnc'],
        'lac'=>$liveLocationData['lac'],
        'cell_id'=>$liveLocationData['cell_id'],
        'created_at' => now(),
        'serial_number'=>$liveLocationData['serial_number'],
        'error_check'=>$liveLocationData['error_check'],
        'event'=>$liveLocationData['event'],
        'parse_time'=>$liveLocationData['parse_time']
    ]);
}

// Helper function to calculate distance between two GPS coordinates
// private function haversineGreatCircleDistance($latitudeFrom, $longitudeFrom, $latitudeTo, $longitudeTo, $earthRadius = 6371000)
// {
//     // Convert from degrees to radians
//     $latFrom = deg2rad($latitudeFrom);
//     $lonFrom = deg2rad($longitudeFrom);
//     $latTo = deg2rad($latitudeTo);
//     $lonTo = deg2rad($longitudeTo);

//     // Haversine formula
//     $latDelta = $latTo - $latFrom;
//     $lonDelta = $lonTo - $lonFrom;

//     $a = sin($latDelta / 2) * sin($latDelta / 2) +
//          cos($latFrom) * cos($latTo) *
//          sin($lonDelta / 2) * sin($lonDelta / 2);
//     $c = 2 * atan2(sqrt($a), sqrt(1 - $a));

//     return $earthRadius * $c; // Returns distance in meters
// }


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
    // Get the authenticated user
    $user = Auth::guard('sanctum')->user();
    if (!$user) {
        return response()->json(['error' => 'Invalid Access'], 422);
    }

    // Fetch vehicles based on user type with eager loading
    $vehiclesQuery = Vehicle::with(['owner', 'tracker', 'lastLocation', 'speedLimit'])
        ->when(!in_array($user->user_type, ['admin', 'system_admin']), function ($query) use ($user) {
            return $query->where('user_id', $user->id);
        });

    $vehicles = $vehiclesQuery->get();

    // Check if any vehicles were found
    if ($vehicles->isEmpty()) {
        return response()->json(['message' => 'No vehicles found'], 404);
    }

    // Prepare result array to hold information for all vehicles
    $results = $vehicles->map(function ($vehicle) {
        // Ensure the tracker is loaded and available
        if (!$vehicle->tracker) {
            return [
                'vehicle' => [
                    'id' => $vehicle->id,
                    'error' => 'Tracker not found',
                    'status' => 404,
                ],
            ];
        }

        // Retrieve the latest location for the tracker
        $lastLocation = $vehicle->lastLocation;

        if (!$lastLocation) {
            return [
                'vehicle' => [
                    'id' => $vehicle->id,
                    'error' => 'Location not found',
                    'status' => 404,
                ],
            ];
        }

        // Determine connected status
        $connectedStatus = (isset($lastLocation->updated_at) && (strtotime($lastLocation->updated_at) > (time() - 900)))
            ? 'Connected'
            : 'Not connected';

        // Prepare result array for this specific vehicle
        return [
            'vehicle' => [
                'id' => $vehicle->id,
                'details' => $vehicle, // Add vehicle details
                'driver' => Driver::where('vehicle_vin', $vehicle->vin)->first(),
                'address' => $this->getAddressFromCoordinates($lastLocation->latitude, $lastLocation->longitude),
                'geofence' => $this->checkInGeofence($vehicle->id, $lastLocation->latitude, $lastLocation->longitude),
                'connected_status' => $connectedStatus,
            ],
        ];
    });

    // Filter out vehicles that have errors (e.g., tracker not found)
    $filteredResults = $results->filter(function ($result) {
        return !isset($result['vehicle']['error']);
    });

    // If all vehicles have errors, return an error response
    if ($filteredResults->isEmpty()) {
        return response()->json(['message' => 'No valid vehicle data found'], 404);
    }

    // Return the valid vehicle data
    return response()->json($filteredResults->values(), 200);
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


function getAddressFromCoordinates($latitude, $longitude) {
    $cacheKey = "reverse_geocode_{$latitude}_{$longitude}";
    $address = Cache::get($cacheKey);

    if (!$address) {
        // Use your geocoding API here, e.g., Google Maps
        //switch to open street map
        $apiKey = env('GOOGLE_MAP_KEY');
        $response = Http::get("https://maps.googleapis.com/maps/api/geocode/json", [
            'latlng' => "{$latitude},{$longitude}",
            'key' => $apiKey,
        ]);

        $data = $response->json();

        if ($data['status'] === 'OK' && !empty($data['results'])) {
            $address = $data['results'][0]['formatted_address'];
            Cache::put($cacheKey, $address, now()->addMinutes(1440)); // Cache for 10 minutes
        } else {
            $address = 'Address not found';
        }
    }

    return $address;
}
//change with batch asap
private function updateVehicleStatus($data)
{
    // Use updateOrCreate to either update the existing record or create a new one
    $status = VehicleStatus::updateOrCreate(
        ['vehicle_vin' => $data['vehicle_vin']],
        [
            'vehicle_id' => $data['vehicle_id'],
            'vehicle_status' => $data['vehicle_status'],
            'device_id'=>$data['device_id']
        ]
    );

    return $status; // Optional: Return the updated or created VehicleStatus record
}
// public function get_location_history(Request $request)
// {
//     // Define validation rules
//     $rules = [
//         'vehicle_vin' => 'required|string|exists:vehicles,vin', // Assuming a 'vehicles' table with 'vin' column exists
//         'time_from' => 'required|date_format:Y-m-d H:i:s', // Ensuring date format is specific
//         'time_to' => 'nullable|date_format:Y-m-d H:i:s|after_or_equal:time_from', // Make 'time_to' optional
//     ];

//     // Validate the request input
//     $validator = Validator::make($request->all(), $rules);

//     // If validation fails, return errors
//     if ($validator->fails()) {
//         return response()->json(['errors' => $validator->errors()], 422);
//     }

//     // Retrieve validated input data and parse them using Carbon
//     $vehicleVin = $request->input('vehicle_vin');
//     $vehicleId = Vehicle::where('vin', strip_tags($vehicleVin))->first()->id;

//     $timeFrom = Carbon::parse($request->input('time_from'));
//     $timeTo = $request->input('time_to') ? Carbon::parse($request->input('time_to')) : null;

//     // Query for location history data using 'updated_at' column
//     $query = HistoricalLocation::where('vehicle_id', $vehicleId)
//         ->where('updated_at', '>=', $timeFrom) // Get records from time_from onwards
//         ->orderBy('updated_at', 'desc');

//     if ($timeTo) {
//         $query->where('updated_at', '<=', $timeTo); // Add time_to condition if provided
//     }

//     $locationHistory = $query->get();

//     // Check if location history data is found
//     if ($locationHistory->isEmpty()) {
//         // If no location history is found, get the last known location before time_from
//         $lastLocation = HistoricalLocation::where('vehicle_id', $vehicleId)
//             ->where('updated_at', '<', $timeFrom) // Get the last location before time_from
//             ->orderBy('updated_at', 'desc') // Order by updated_at in descending order
//             ->first();

//         if ($lastLocation) {
//             return response()->json(['data' => [$lastLocation]], 200); // Return the last known location
//         }

//         return response()->json(['message' => 'No location history found for the given parameters'], 404);
//     }

//     // Format the location data timestamps with Carbon before returning (optional)
//     $formattedHistory = $locationHistory->map(function ($location) {
//         $location->updated_at = Carbon::parse($location->updated_at)->format('Y-m-d H:i:s'); // Formatting the timestamp as needed
//         return $location;
//     });

//     // Return the location history data
//     return response()->json(['data' => $formattedHistory], 200);
// }


public function get_location_history(Request $request)
{
    // Define validation rules
    $rules = [
        'vehicle_vin' => 'required|string|exists:vehicles,vin', // Assuming a 'vehicles' table with 'vin' column exists
        'time_from' => 'required|date_format:Y-m-d H:i:s', // Ensuring date format is specific
        'time_to' => 'nullable|date_format:Y-m-d H:i:s|after_or_equal:time_from', // Make 'time_to' optional
    ];

    // Validate the request input
    $validator = Validator::make($request->all(), $rules);

    // If validation fails, return errors
    if ($validator->fails()) {
        return response()->json(['errors' => $validator->errors()], 422);
    }

    // Retrieve validated input data and parse them using Carbon
    $vehicleVin = $request->input('vehicle_vin');
    $vehicleId = Vehicle::where('vin', strip_tags($vehicleVin))->first()->id;

    // Convert input times to UTC
    $timeFrom = Carbon::parse($request->input('time_from'))->setTimezone('Africa/Lagos')->setTimezone('UTC');
    $timeTo = $request->input('time_to') ? Carbon::parse($request->input('time_to'))->setTimezone('Africa/Lagos')->setTimezone('UTC') : null;

    // Query for location history data using 'updated_at' column
    $query = HistoricalLocation::where('vehicle_id', $vehicleId)
        ->where('updated_at', '>=', $timeFrom) // Get records from time_from onwards
        ->orderBy('updated_at', 'asc');

    if ($timeTo) {
        $query->where('updated_at', '<=', $timeTo); // Add time_to condition if provided
    }

    $locationHistory = $query->get();

    // Check if location history data is found
    if ($locationHistory->isEmpty()) {
        // If no location history is found, get the last known location before time_from
        $lastLocation = HistoricalLocation::where('vehicle_id', $vehicleId)
            ->where('updated_at', '<', $timeFrom) // Get the last location before time_from
            ->orderBy('updated_at', 'asc') // Order by updated_at in descending order
            ->first();

        if ($lastLocation) {
            return response()->json(['data' => [$lastLocation]], 200); // Return the last known location
        }

        return response()->json(['message' => 'No location history found for the given parameters'], 404);
    }

    // Format the location data timestamps with Carbon before returning (optional)
    $formattedHistory = $locationHistory->map(function ($location) {
        $location->updated_at = Carbon::parse($location->updated_at)->format('Y-m-d H:i:s'); // Formatting the timestamp as needed
        return $location;
    });

    // Return the location history data
    return response()->json(['data' => $formattedHistory], 200);
}





}