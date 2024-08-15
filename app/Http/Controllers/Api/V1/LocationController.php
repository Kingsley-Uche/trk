<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
 use App\Events\VehicleLocationUpdated;
class LocationController extends Controller
{
   

        public function updateLocation(Request $request)
        {
                $this->sendLocation();
                return response()->json(['status' => 'Location updated and broadcasted']);
            // Update car location logic
        //     \Log::info('Tracker data received', $request->all());
        //     broadcast(new VehicleLocationUpdated($car));
        //     $validated = $request->validate([
        //         'tracker_id' => 'required|string',
        //         'latitude' => 'required|numeric',
        //         'longitude' => 'required|numeric',
        //         'timestamp' => 'required|date_format:Y-m-d H:i:s',
        //     ]);
        
        //     // Update the car's location in the database
        //    // $car = Car::where('tracker_id', $validated['tracker_id'])->first();
        
        //     if ($car) {
        //         $car->latitude = $validated['latitude'];
        //         $car->longitude = $validated['longitude'];
        //         $car->last_updated = $validated['timestamp'];
        //         $car->save();
        
        //         // Broadcast the car location update
        //         broadcast(new CarLocationUpdated($car));
        
        //         // Return a JSON response with status
        //         return response()->json(['status' => 'Location updated and broadcasted']);
        //     }
        
            // Return a JSON response with error status
            return response()->json(['status' => 'Tracker ID not found'], 404);
        
        


    }
    private function getTracker(){

       return  $locations = [
            [
                'location_name' => 'Lekki Phase One',
                'tracker_id'=>'ds123h',
                'location' => [
                    'lat' => 6.4372493,
                    'lng' => 3.4593348
                ]
            ],
            [
                'location_name' => 'Berger',
                'tracker_id'=>'asd23h',
                'location' => [
                    'lat' => 6.6417125,
                    'lng' => 3.3659572
                ]
            ],
            [
                'location_name' => 'Awolowo Way',
                'tracker_id'=>'ghd23w',
                'location' => [
                    'lat' => 6.5966571,
                    'lng' => 3.3378159
                ]
            ]
        ];
        
    }

    private function sendLocation(){
        $details = getTracker();
        broadcast(new VehicleLocationUpdated($details));
    }
}
