<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Trip;
use App\Models\Trip_Location as TripLocation;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Auth;
use Carbon\Carbon;

// Example data to insert
$departureTime = '2024-09-09T14:30:00.000Z'; // ISO 8601 format
$arrivalTime = '2024-09-10T14:30:00.000Z';

// Convert to MySQL-compatible format without seconds
$formattedDepartureTime = Carbon::parse($departureTime)->format('Y-m-d H:i');
$formattedArrivalTime = Carbon::parse($arrivalTime)->format('Y-m-d H:i');

class TripController extends Controller
{
    /**
     * Display a listing of the trips.
     */
    // public function index()
    // {
    //      $user = Auth::guard('sanctum')->user();
        
    //     if (!$this->isAuthorized($user)) {
    //         return response()->json(['error' => 'Access not permitted for this user type'], 403);
    //     }
    //     $trips = Trip::with('tripLocations')->get();
    //     return response()->json($trips, 200);
    // }
    public function index()
{
    $user = Auth::guard('sanctum')->user();

    if (!$this->isAuthorized($user)) {
        return response()->json(['error' => 'Access not permitted for this user type'], 403);
    }

    // Retrieve trips with trip locations ordered by updated_at in descending order
    $trips = Trip::with(['tripLocations' => function ($query) {
        $query->orderBy('updated_at', 'desc'); // Order trip locations by updated_at in descending order
    }])->get();

    return response()->json($trips, 200);
}


    /**
     * Store a newly created trip in storage.
     */
   

// public function store(Request $request)
// {
//     $user = Auth::guard('sanctum')->user();
    
//     if (!$this->isAuthorized($user)) {
//         return response()->json(['error' => 'Access not permitted for this user type'], 403);
//     }

//     // Validate the request
//     $validator = Validator::make($request->all(), [
//         'name' => 'required|string|max:255',
//         'start_location' => 'required|array',
//         'start_location.*' => 'required|string',
//         'start_lat' => 'required|array',
//         'start_lat.*' => 'required|numeric|between:-90,90', // Validate latitude
//         'start_lon' => 'required|array',
//         'start_lon.*' => 'required|numeric|between:-180,180', // Validate longitude
//         'end_location' => 'required|array',
//         'end_location.*' => 'required|string',
//         'end_lat' => 'required|array',
//         'end_lat.*' => 'required|numeric|between:-90,90', // Validate latitude
//         'end_lon' => 'required|array',
//         'end_lon.*' => 'required|numeric|between:-180,180', // Validate longitude
//         'departure_time' => 'required|array',
//         'departure_time.*' => 'required|date',
//         'arrival_time' => 'nullable|array',
//         'arrival_time.*' => 'nullable|date|after:departure_time.*',
//         'driver_id' => 'required|exists:drivers,id',
//         'vehicle_vin' => 'required|exists:vehicles,vin',
//         'description' => 'nullable|string',
//     ]);

//     if ($validator->fails()) {
//         return response()->json(['errors' => $validator->errors()], 422);
//     }

//     // Extracting the arrays
//     $departureTimes = $request->input('departure_time');
//     $arrivalTimes = $request->input('arrival_time');
//     $startLocations = $request->input('start_location');
//     $startLats = $request->input('start_lat');
//     $startLons = $request->input('start_lon');
//     $endLocations = $request->input('end_location');
//     $endLats = $request->input('end_lat');
//     $endLons = $request->input('end_lon');
    
//     // Check for equal number of departure and arrival times, and locations
//     if (count($departureTimes) !== count($arrivalTimes) || 
//         count($departureTimes) !== count($startLocations) || 
//         count($departureTimes) !== count($endLocations)) {
//         return response()->json(['error' => 'The number of departure times, arrival times, and locations must be equal.'], 400);
//     }

//     // Check for overlapping trips
//     foreach ($departureTimes as $index => $departureTime) {
//         $arrivalTime = $arrivalTimes[$index];
//         $startLocation = $startLocations[$index];
//         $startLat = $startLats[$index];
//         $startLon = $startLons[$index];
//         $endLocation = $endLocations[$index];
//         $endLat = $endLats[$index];
//         $endLon = $endLons[$index];

//         $overlappingTrips = Trip::join('trip_locations', 'trips.id', '=', 'trip_locations.trip_id')
//             ->where(function ($query) use ($departureTime, $arrivalTime, $startLocation, $startLat, $startLon, $endLocation, $endLat, $endLon) {
//                 $query->whereBetween('trip_locations.departure_time', [$departureTime, $arrivalTime])
//                       ->orWhereBetween('trip_locations.arrival_time', [$departureTime, $arrivalTime])
//                       ->orWhere(function ($query) use ($departureTime, $arrivalTime) {
//                           $query->where('trip_locations.departure_time', '<=', $departureTime)
//                                 ->where('trip_locations.arrival_time', '>=', $arrivalTime);
//                       })
//                       ->where('trip_locations.start_location', $startLocation)
//                       ->where('trip_locations.start_lat', $startLat)
//                       ->where('trip_locations.start_lon', $startLon)
//                       ->where('trip_locations.end_location', $endLocation)
//                       ->where('trip_locations.end_lat', $endLat)
//                       ->where('trip_locations.end_lon', $endLon);
//             })
//             ->exists();

//         if ($overlappingTrips) {
//             return response()->json(['error' => 'Trip overlaps with an existing trip'], 409);
//         }
//     }

//     // Generate a unique trip ID
//     $trip_id = $this->generateUniqueTripId();

//     // Create the trip
//     $trip = Trip::create(array_merge($request->all(), ['trip_id' => $trip_id]));

//     // Save trip location data
//     $this->saveTripLocations($request, $trip->id);

//     return response()->json(['message' => 'Trip created successfully', 'trip' => $trip], 201);
// }
public function store(Request $request)
{
    $user = Auth::guard('sanctum')->user();
    
    if (!$this->isAuthorized($user)) {
        return response()->json(['error' => 'Access not permitted for this user type'], 403);
    }

    // Validate the request
    $validator = Validator::make($request->all(), [
        'name' => 'required|string|max:255',
        'start_location' => 'required|array',
        'start_location.*' => 'required|string',
        'start_lat' => 'required|array',
        'start_lat.*' => 'required|numeric|between:-90,90',
        'start_lon' => 'required|array',
        'start_lon.*' => 'required|numeric|between:-180,180',
        'end_location' => 'required|array',
        'end_location.*' => 'required|string',
        'end_lat' => 'required|array',
        'end_lat.*' => 'required|numeric|between:-90,90',
        'end_lon' => 'required|array',
        'end_lon.*' => 'required|numeric|between:-180,180',
        'departure_time' => 'required|array',
        'departure_time.*' => 'required|date',
        'arrival_time' => 'nullable|array',
        'arrival_time.*' => 'nullable|date|after_or_equal:departure_time.*',
        'driver_id' => 'required|exists:drivers,id',
        'vehicle_vin' => 'required|exists:vehicles,vin',
        'description' => 'nullable|string',
    ]);

    if ($validator->fails()) {
        return response()->json(['errors' => $validator->errors()], 422);
    }

    // Extract data from the request
    $departureTimes = $request->input('departure_time');
    $arrivalTimes = $request->input('arrival_time');
    $startLocations = $request->input('start_location');
    $startLats = $request->input('start_lat');
    $startLons = $request->input('start_lon');
    $endLocations = $request->input('end_location');
    $endLats = $request->input('end_lat');
    $endLons = $request->input('end_lon');
    
    // Check for equal number of locations and times
    if (
        count($departureTimes) !== count($startLocations) || 
        count($startLocations) !== count($endLocations)
    ) {
        return response()->json(['error' => 'The number of times and locations must be equal.'], 400);
    }

    // Check for overlapping trips
    foreach ($departureTimes as $index => $departureTime) {
        $formattedDepartureTime = Carbon::parse($departureTime)->format('Y-m-d H:i');
        $formattedArrivalTime = Carbon::parse($arrivalTimes[$index] ?? null)->format('Y-m-d H:i');
        $startLocation = $startLocations[$index];
        $startLat = $startLats[$index];
        $startLon = $startLons[$index];
        $endLocation = $endLocations[$index];
        $endLat = $endLats[$index];
        $endLon = $endLons[$index];

        $overlappingTrips = Trip::join('trip_locations', 'trips.id', '=', 'trip_locations.trip_id')
            ->where(function ($query) use ($formattedDepartureTime, $formattedArrivalTime) {
                $query->whereBetween('trip_locations.departure_time', [$formattedDepartureTime, $formattedArrivalTime])
                      ->orWhereBetween('trip_locations.arrival_time', [$formattedDepartureTime, $formattedArrivalTime])
                      ->orWhere(function ($query) use ($formattedDepartureTime, $formattedArrivalTime) {
                          $query->where('trip_locations.departure_time', '<=', $formattedDepartureTime)
                                ->where('trip_locations.arrival_time', '>=', $formattedArrivalTime);
                      });
            })
            ->where('trip_locations.start_location', $startLocation)
            ->where('trip_locations.start_lat', $startLat)
            ->where('trip_locations.start_lon', $startLon)
            ->where('trip_locations.end_location', $endLocation)
            ->where('trip_locations.end_lat', $endLat)
            ->where('trip_locations.end_lon', $endLon)
            ->exists();

        if ($overlappingTrips) {
            return response()->json(['error' => 'Trip overlaps with an existing trip'], 409);
        }
    }

    // Generate a unique trip ID
    $trip_id = $this->generateUniqueTripId();

    // Create the trip
    $trip = Trip::create([
        'name' => $request->input('name'),
        'driver_id' => $request->input('driver_id'),
        'vehicle_vin' => $request->input('vehicle_vin'),
        'description' => $request->input('description'),
        'trip_id' => $trip_id,
    ]);

    // Save trip location data
    foreach ($departureTimes as $index => $departureTime) {
        TripLocation::create([
            'trip_id' => $trip->id,
            'start_location' => $startLocations[$index],
            'start_lat' => $startLats[$index],
            'start_lon' => $startLons[$index],
            'end_location' => $endLocations[$index],
            'end_lat' => $endLats[$index],
            'end_lon' => $endLons[$index],
            'departure_time' => Carbon::parse($departureTime)->format('Y-m-d H:i'),
            'arrival_time' => isset($arrivalTimes[$index]) ? Carbon::parse($arrivalTimes[$index])->format('Y-m-d H:i') : null,
        ]);
    }

    return response()->json(['message' => 'Trip created successfully', 'trip' => $trip], 201);
}

    /**
     * Display the specified trip.
     */
    public function show($id)
    {
        
        $user = Auth::guard('sanctum')->user();
        
        if (!$this->isAuthorized($user)) {
            return response()->json(['error' => 'Access not permitted for this user type'], 403);
        }
    
        $trip = Trip::with('tripLocations')->find($id);

        if (!$trip) {
            return response()->json(['message' => 'Trip not found'], 404);
        }

        return response()->json($trip, 200);
    }

    /**
     * Update the specified trip in storage.
     */
    public function update(Request $request, $id)
    {
       
       
        $user = Auth::guard('sanctum')->user();
        
        if (!$this->isAuthorized($user)) {
            return response()->json(['error' => 'Access not permitted for this user type'], 403);
        }
    
        $trip = Trip::find($id);

        if (!$trip) {
            return response()->json(['message' => 'Trip not found'], 404);
        }

        // Validate the request
        $validator = Validator::make($request->all(), [
            'name' => 'sometimes|required|string|max:255',
            'start_location.*' => 'sometimes|required|string',
            'start_lat.*' => 'sometimes|required|numeric|between:-90,90', // Validate latitude
            'start_lon.*' => 'sometimes|required|numeric|between:-180,180', // Validate longitude
            'end_location.*' => 'sometimes|required|string',
            'end_lat.*' => 'sometimes|required|numeric|between:-90,90', // Validate latitude
            'end_lon.*' => 'sometimes|required|numeric|between:-180,180', // Validate longitude
            'departure_time.*' => 'sometimes|required|date',
            'arrival_time.*' => 'nullable|date|after:departure_time.*',
            'driver_id' => 'sometimes|required|exists:drivers,id',
            'vehicle_vin' => 'sometimes|required|exists:vehicles,vin',
            'description' => 'nullable|string',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        // Update the trip
        $trip->update($request->all());

        // Update trip location data
        $this->saveTripLocations($request, $trip->trip_id);

        return response()->json(['message' => 'Trip updated successfully', 'trip' => $trip], 200);
    }

    /**
     * Remove the specified trip from storage.
     */
    public function destroy($id)
    {
        
        
       $user = Auth::guard('sanctum')->user();
        
        if (!$this->isAuthorized($user)) {
            return response()->json(['error' => 'Access not permitted for this user type'], 403);
        }
    
        $trip = Trip::find($id);

        if (!$trip) {
            return response()->json(['message' => 'Trip not found'], 404);
        }

        // Delete associated trip locations
        $trip->tripLocations()->delete();

        $trip->delete();

        return response()->json(['message' => 'Trip deleted successfully'], 200);
    }

    /**
     * Generate a unique trip ID.
     */
    private function generateUniqueTripId()
    {
        do {
            // Generate a potential trip ID
            $trip_id = 'trip_' . now()->format('YmdHis') . '_' . bin2hex(random_bytes(6));
        } while (Trip::where('trip_id', $trip_id)->exists());

        return $trip_id;
    }

    /**
     * Save trip location data.
     */
    private function saveTripLocations(Request $request, $trip_id)
    {
        $locations = $request->only([
            'start_location',
            'start_lat',
            'start_lon',
            'end_location',
            'end_lat',
            'end_lon',
            'departure_time',
            'arrival_time'
        ]);

        $start_locations = $locations['start_location'];
        $start_lats = $locations['start_lat'];
        $start_lons = $locations['start_lon'];
        $end_locations = $locations['end_location'];
        $end_lats = $locations['end_lat'];
        $end_lons = $locations['end_lon'];
        $departure_times = $locations['departure_time'];
        $arrival_times = $locations['arrival_time'] ?? array_fill(0, count($start_locations), null);

        foreach ($start_locations as $index => $start_location) {
            TripLocation::updateOrCreate(
                ['trip_id' => $trip_id, 'start_location' => $start_location, 'start_lat' => $start_lats[$index], 'start_lon' => $start_lons[$index]],
                [
                    'end_location' => $end_locations[$index],
                    'end_lat' => $end_lats[$index],
                    'end_lon' => $end_lons[$index],
                    'departure_time' => $departure_times[$index],
                    'arrival_time' => $arrival_times[$index]
                ]
            );
        }
    }
    
     private function isAuthorized($user)
    {
        return in_array($user->user_type, ['admin', 'system_admin']);
    }

}
