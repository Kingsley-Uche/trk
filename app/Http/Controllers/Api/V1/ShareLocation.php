<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;
use App\Notifications\SendEmail;
use App\Models\EmailRecipient;
class ShareLocation extends Controller
{
    public function sendLocation(Request $request)
    {
        $user = Auth::guard('sanctum')->user();

        if (!$user || !in_array($user->user_type, ['admin', 'system_admin','user'])) {
            return response()->json(['error' => 'Invalid Access'], 403);
        }

        $validator = Validator::make($request->all(), [
            'url' => ['required', 'string'],
            'email' => ['required', 'email'],
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $data = [
            'email' => strip_tags($request->email),
            'url' => strip_tags($request->url),
            'subject' => 'Vehicle Location Shared',
            'message' => 'Use the link below to track the vehicle in real-time'
        ];
$emailRecipient = new EmailRecipient($data['email']);
// Send the notification using the notify method
$emailRecipient->notify(new SendEmail($data));

        return response()->json(['success' => true, 'message' => 'Location shared successfully'], 201);
    }
}
