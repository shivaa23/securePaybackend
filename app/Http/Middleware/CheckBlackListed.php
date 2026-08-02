<?php

namespace App\Http\Middleware;

use Closure;
use App\Modules\Risk\Models\BlackListedAcc;
use App\Helper\ResponseHelper;

class CheckBlackListed
{
    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure  $next
     * @return mixed
     */
    public function handle($request, Closure $next)
    {
        // Get account number from request
        $acc_no = $request->acc_no ?? $request->account_number;
    
        // If no account number is provided, skip and continue
        if (!$acc_no) {
            return $next($request);
        }
    
        // Check if account is blacklisted
        $isBlackListed = BlackListedAcc::where('acc_no', $acc_no)->exists();
    
        if ($isBlackListed) {
            return ResponseHelper::error('Transaction blocked: Account is black-listed', [], 403);
        }
    
        return $next($request);
    }

}
