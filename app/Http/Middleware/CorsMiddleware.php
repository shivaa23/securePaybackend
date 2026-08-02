<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Support\Facades\App;

class CorsMiddleware
{
    public function handle($request, Closure $next)
    {
        // ✅ Environment-based domain control
        $origin = match (App::environment()) {
            'production' => 'https://app.transup.in',
            default      => '*'
        };

        $headers = [
            'Access-Control-Allow-Origin'      => $origin,
            'Access-Control-Allow-Methods'     => 'GET, POST',
            'Access-Control-Allow-Credentials' => 'true',
            'Access-Control-Max-Age'           => '86400',
            'Access-Control-Allow-Headers'     => 'Content-Type, Authorization, X-Requested-With, Origin, Accept, Referer, User-Agent',
        ];

        // ✅ Handle preflight (OPTIONS) requests
        if ($request->getMethod() === 'OPTIONS') {
            return response('', 204, $headers);
        }

        /** @var \Illuminate\Http\Response|\Laravel\Lumen\Http\ResponseFactory $response */
        $response = $next($request);

        // ✅ Add headers safely to all response types
        foreach ($headers as $key => $value) {
            $response->headers->set($key, $value);
        }

        return $response;
    }
}
