<?php
namespace App\Http\Middleware;

use Closure;
use Illuminate\Support\Facades\Auth;

class CheckTokenInactivity
{
    public function handle($request, Closure $next)
    {
        $user = Auth::user();
        if ($user) {
            $lastActivity = $user->last_activity; // Assuming you have this field
            if ($lastActivity && now()->diffInMinutes($lastActivity) > config('sanctum.expiration')) {
                Auth::logout(); // Invalidate the token
                return response()->json(['message' => 'Token expired due to inactivity.'], 401);
            }
        }

        return $next($request);
    }
}