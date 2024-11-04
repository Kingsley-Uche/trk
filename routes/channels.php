<?php

use Illuminate\Support\Facades\Broadcast;

// Broadcast::channel('App.Models.User.{id}', function ($user, $id) {
//     return (int) $user->id === (int) $id;
// });



Broadcast::channel('admin.vehicle-location', function ($user) {
    return in_array($user->user_type, ['admin', 'system_admin']); // Allow both 'admin' and 'system_admin' roles
});


// Channel for each vehicle owner to receive updates specific to their vehicles
Broadcast::channel('vehicle-owner.{vehicleOwnerId}', function ($user, $vehicleOwnerId) {
    return $user->id === (int) $vehicleOwnerId; // Only allow the specific vehicle owner
});