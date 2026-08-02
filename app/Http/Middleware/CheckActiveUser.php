<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Support\Facades\Auth;

class CheckActiveUser
{
    public function handle($request, Closure $next)
    {
        $user = Auth::user();

        if ($user && $user->is_active == 0) {
            return response()->json([
                'code'    => '1001',
                'status'  => 'Failed',
                'message' => 'Deactivated Account Kindly Contact Administrator'
            ], 403);
        }

        return $next($request);
    }
}