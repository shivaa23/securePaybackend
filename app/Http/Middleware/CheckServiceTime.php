<?php

namespace App\Http\Middleware;

use Closure;
use Carbon\Carbon;
use App\Helper\ResponseHelper;
use Illuminate\Support\Facades\Auth;

class CheckServiceTime
{
    /**
     * Handle an incoming request.
     */
    public function handle($request, Closure $next)
    {
        return $next($request);
        $user = Auth::user();
        if ($user && in_array($user->role, ['adm', 'sadm'])) {
            return $next($request);
        }

        $currentTime = Carbon::now('Asia/Kolkata');
        $startHour = 5;   
        $endHour   = 23;  
        if ($currentTime->hour < $startHour || $currentTime->hour >= $endHour) {
            return ResponseHelper::error(
                'Our portal is under maintenance. You can access it after 5:00 AM.',
                [],
                503
            );
        }

        return $next($request);
    }
}
