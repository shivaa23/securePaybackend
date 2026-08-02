<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Support\Facades\Auth;

class CheckWhitelistedIp
{
    public function handle($request, Closure $next)
    {
        $user = Auth::user();
        $requestIp = $request->ip();
        $whitelistedIps = is_array($user->whitelisted_ips)
            ? $user->whitelisted_ips
            : explode(',', (string) $user->whitelisted_ips);
            $whitelistedIps = array_filter($whitelistedIps); 
        if ($user->role === 'api') {
            if (empty($whitelistedIps) || !in_array($requestIp, $whitelistedIps)) {
                return response()->json([
                    'status'  => 'failed',
                    'message' => 'Unauthorized: IP not whitelisted for API user',
                ], 403);
            }
        } 
        else {
            if (!empty($whitelistedIps) && !in_array($requestIp, $whitelistedIps)) {
                return response()->json([
                    'status'  => 'failed',
                    'message' => 'Unauthorized: IP not whitelisted for this user',
                ], 403);
            }
        }
        return $next($request);
    }
}