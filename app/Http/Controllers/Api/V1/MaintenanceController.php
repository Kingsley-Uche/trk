<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;
use App\Models\ScheduleModel;

class MaintenanceController extends Controller
{
    public function createSchedule(Request $request)
    {
        $user = Auth::guard('sanctum')->user();

        if (!$this->isAuthorized($user)) {
            return response()->json(['error' => 'Access not permitted for this user type'], 403);
        }

        // Initial validation
        $validator = Validator::make($request->all(), [
            'description' => ['nullable', 'string'],
            'vehicle_vin' => ['required', 'string', 'exists:vehicles,vin'],
            'schedule_type' => ['required', 'in:custom,recurring'], // Corrected validation
            'start_date' => ['nullable', 'date_format:Y-m-d'], // Proper date format
            'number_time' => ['nullable', 'numeric'],
            'number_kilometer' => ['nullable', 'numeric'],
            'number_hours' => ['nullable', 'numeric'],
            'category_time' => ['nullable', 'string', 'in:days,weeks,months,years'], // Corrected in validation
            'reminder_advance_days' => ['nullable', 'numeric'],
            'reminder_advance_km' => ['nullable', 'numeric'],
            'reminder_advance_hr' => ['nullable', 'numeric'],
        ]);

        // Check for recurring validation
        if ($request->input('schedule_type') === 'recurring') {
            $validator->after(function ($validator) use ($request) {
                if (!$request->has('start_date')) {
                    $validator->errors()->add('start_date', 'The start date is required for recurring schedules.');
                }
            });
        }

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        // Creating the schedule
        $schedule = ScheduleModel::create([
            'description' => $request->input('description'),
            'vehicle_vin' => $request->input('vehicle_vin'),
            'schedule_type' => $request->input('schedule_type'),
            'start_date' => $request->input('start_date'),
            'no_time' => $request->input('number_time'),
            'no_kilometer' => $request->input('number_kilometer'),
            'no_hours' => $request->input('number_hours'),
            'category_time' => $request->input('category_time'),
            'reminder_advance_days' => $request->input('reminder_advance_days'),
            'reminder_advance_km' => $request->input('reminder_advance_km'),
            'reminder_advance_hr' => $request->input('reminder_advance_hr'),
            'start_date' => $request->input('start_date'),
        ]);

        return response()->json(['message' => 'Schedule created successfully', 'schedule' => $schedule], 201);
    }
        private function isAuthorized($user)
    {
        return in_array($user->user_type, ['admin', 'system_admin','user']);
    }

    // Helper method to sanitize input
   
}
