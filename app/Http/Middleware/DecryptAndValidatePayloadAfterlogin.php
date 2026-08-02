<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use App\Helpers\ResponseHelper;
use App\Traits\DecryptPayloadTraitAfterLogin;

class DecryptAndValidatePayloadAfterlogin
{
    use DecryptPayloadTraitAfterLogin;

public function handle(Request $request, Closure $next)
{

    if ($request->isMethod('get')) {
        if (count($request->query()) === 0) {
            return $next($request);
        }
        $input = $request->query();

        if (isset($input['data']) && isset($input['hash'])) {
            $decrypted = $this->decryptPayload($input['data'], $input['hash']);

            if (!is_array($decrypted)) {
                return response()->json([
                    'status' => false,
                    'message' => 'Decryption failed or returned invalid format',
                    'errors' => []
                ], 400);
            }

            if (isset($decrypted['error'])) {
                return response()->json([
                    'status' => false,
                    'message' => $decrypted['error'],
                    'errors' => []
                ], 400);
            }
            $request->query->replace($decrypted);
            return $next($request);
        } else {
            return response()->json([
                'status' => false,
                'message' => 'Missing data or hash',
                'errors' => []
            ], 422);
        }
    }

    $input = $request->all();

    if (isset($input['data']) && isset($input['hash'])) {
        $decrypted = $this->decryptPayload($input['data'], $input['hash']);

        if (!is_array($decrypted)) {
            return response()->json([
                'status' => false,
                'message' => 'Decryption failed or returned invalid format',
                'errors' => []
            ], 400);
        }

        if (isset($decrypted['error'])) {
            return response()->json([
                'status' => false,
                'message' => $decrypted['error'],
                'errors' => []
            ], 400);
        }

        $request->replace($decrypted);
        return $next($request);
    }

    return response()->json([
        'status' => false,
        'message' => 'Missing data or hash',
        'errors' => []
    ], 422);
}


}
