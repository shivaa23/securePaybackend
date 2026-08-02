<?php

namespace App\Http\Middleware;

use Closure;
use App\Modules\Risk\Models\WhiteListedAcc;
use App\Modules\Payout\Models\PayoutTxn;
use App\Helper\ResponseHelper;
use Auth;
use Carbon\Carbon;
class CheckWhiteListedAcc
{
    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure  $next
     * @return mixed
     */
    public function handle($request, Closure $next){
        $acc_no = $request->acc_no ?? $request->account_number;
        $user   = Auth::user();
    
        // Check user payout limit
        if ($request->amount > $user->payout_limit) {
            return ResponseHelper::error('Transaction amount greater than payout limit', [], 403);
        }
    
        // Calculate total transactions for this account
        $sum = PayoutTxn::where('account_number', $acc_no)
            ->whereIn('status', ['SUCCESS', 'PENDING'])
            ->whereMonth('created_at', Carbon::now()->month)
            ->sum('amount');
    
        $newTotal = $sum + $request->amount;
    
        // Global account limit check (₹2,50,000)
        if ($newTotal > 250000) {
            // Check if account is whitelisted with higher limit
            $whiteListedAcc = WhiteListedAcc::where('acc_no', $acc_no)->first();
    
            if ($whiteListedAcc && $newTotal <= $whiteListedAcc->limit) {
                // allowed since whitelist limit is higher
                return $next($request);
            }
    
            return ResponseHelper::error('Account Monthly Limit Exceeds', [], 403);
        }
    
        return $next($request);
    }
}
