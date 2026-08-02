<?php

namespace App\Providers;

// use App\Models\User;
use App\Modules\User\Models\User;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\ServiceProvider;
use Carbon\Carbon;

class AuthServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     *
     * @return void
     */
    public function register()
    {
        //
    }

    /**
     * Boot the authentication services for the application.
     *
     * @return void
     */
    public function boot()
    {
        $this->app['auth']->viaRequest('api', function ($request) {
            // 1. Prefer Authorization header (Bearer token)
            $token = $request->bearerToken();
            //$agent = $request->header('User-Agent');
            // 2. Fallback to request input
            if (!$token && $request->has('api_token')) {
                $token = $request->input('api_token');
            }
            if ($token) {
                // if (!str_contains($agent, 'ImpsGuru')) {
                //     return null; //lock anything else (e.g., Postman, curl)
                // }
                return User::where('api_token', $token)->first();
            }
            
            if ($request->has('qr_token')) {
                $qrSession = QrSession::where('token', $request->qr_token)
                    ->where('status', 'approved')
                    ->where('expires_at', '>', Carbon::now())
                    ->first();
        
                if ($qrSession && $qrSession->user_id) {
                    return User::find($qrSession->user_id);
                }
            }
            return null;
        });
    }
}
