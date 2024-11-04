<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;


class JsonResponse
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        
         $request->headers->set('Accept', 'application/json');
        $response = $next($request);
    

        if (! $request->expectsJson()) {
            return $this->redirectTo($request);
        }

        return $response;
    }

    protected function redirectTo($request)
    {
        return route('login');
    }
}