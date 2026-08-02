<?php
namespace App\Modules\Dashboard\Controllers;
use Laravel\Lumen\Routing\Controller as BaseController;
use Illuminate\Http\Request;
use DB;
use App\Helper\ResponseHelper;
use Illuminate\Support\Facades\Crypt;
use App\Modules\User\Models\User;
use Carbon\Carbon;
use Auth;


class DashboardController extends BaseController
{
    
    public function __construct(){
        $this->middleware('auth', ['except' => []]);
        $this->middleware('check.active', ['except' => []]);
    }
    public function getRoleWiseCounts(){
        if(Auth::user()->role != 'adm' && Auth::user()->role != 'sadm'){
            return ResponseHelper::error('Unauthorised Access', '', 403);
        }
        $allowedRoles = ['md', 'di', 'ret', 'dd', 'api','adm'];
        $users = User::whereIn('role', $allowedRoles)
                     ->get(['id', 'role', 'w1', 'w2', 'w3']);
        $result = $users->groupBy('role')->map(function ($group, $role) {
            return [
                'role'        => $role,
                'total_users' => $group->count(),
                'total_w1'    => $group->sum('w1')/100,
                'total_w2'    => $group->sum('w2')/100,
                'total_w3'    => $group->sum('w3')/100,
            ];
        })->values();
        return ResponseHelper::success('Role-wise counts', $result, 200);
    }
    
    public function getServiceWiseSummary(){
        if(Auth::user()->role != 'adm' && Auth::user()->role != 'sadm'){
            return ResponseHelper::error('Unauthorised Access', '', 403);
        }
        $services = [
            'dmt'      => \App\Modules\Dmt\Models\DmtTxn::class,
            'recharge' => \App\Modules\Recharge\Models\RechargeTxn::class,
            'payout'   => \App\Modules\Payout\Models\PayoutTxn::class,
            'aeps'     => \App\Modules\Aeps\Models\AepsTxn::class,
            'bbps'     => \App\Modules\Bbps\Models\BbpsTxn::class,
            'matm'     => \App\Modules\Matm\Models\MatmTxn::class,
            'Credit Card'     => \App\Modules\CreditCard\Models\CreditCard::class,
            // add more services here
        ];
        $today          = \Carbon\Carbon::today();
        $startOfMonth   = \Carbon\Carbon::now()->startOfMonth();
        $endOfMonth     = \Carbon\Carbon::now()->endOfMonth();
        $lastMonthStart = \Carbon\Carbon::now()->subMonth()->startOfMonth();
        $lastMonthEnd   = \Carbon\Carbon::now()->subMonth()->endOfMonth();
    
        $result = [];
    
        foreach ($services as $serviceName => $model) {
            $summary = $model::where('status', 'SUCCESS')->orWhere('status', 'PENDING')
                ->selectRaw('COUNT(*) as total_txns')
                ->selectRaw('SUM(amount) as total_amount')
                ->selectRaw("SUM(CASE WHEN DATE(created_at) = ? THEN amount ELSE 0 END) as td_amount", [$today])
                ->selectRaw("COUNT(CASE WHEN DATE(created_at) = ? THEN 1 END) as td_count", [$today])
                ->selectRaw("SUM(CASE WHEN created_at BETWEEN ? AND ? THEN amount ELSE 0 END) as mtd_amount", [$startOfMonth, $endOfMonth])
                ->selectRaw("COUNT(CASE WHEN created_at BETWEEN ? AND ? THEN 1 END) as mtd_count", [$startOfMonth, $endOfMonth])
                ->selectRaw("SUM(CASE WHEN created_at BETWEEN ? AND ? THEN amount ELSE 0 END) as lmtd_amount", [$lastMonthStart, $lastMonthEnd])
                ->selectRaw("COUNT(CASE WHEN created_at BETWEEN ? AND ? THEN 1 END) as lmtd_count", [$lastMonthStart, $lastMonthEnd])
                ->first();
    
            $result[$serviceName] = $summary;
        }
    
        return ResponseHelper::success('Service wise transaction summary', $result, 200);
    }
    
//     public function getRetTxnSummary(Request $request)
//     {
//     $user = Auth::user();

//     $services = [
//         \App\Modules\Dmt\Models\DmtTxn::class,
//         \App\Modules\Recharge\Models\RechargeTxn::class,
//         \App\Modules\Payout\Models\PayoutTxn::class,
//         \App\Modules\Aeps\Models\AepsTxn::class,
//         \App\Modules\Bbps\Models\BbpsTxn::class,
//         \App\Modules\Matm\Models\MatmTxn::class,
//         \App\Modules\CreditCard\Models\CreditCard::class,
//     ];

//     $combined = collect();

//     foreach ($services as $model) {
//         $query = $model::select(
//             'status',
//             \DB::raw('COUNT(*) as total_txns'),
//             \DB::raw('SUM(amount) as total_amount')
//         );

     
//         if ($user->role == 'adm' || $user->role == 'sadm') {
//             if ($request->filled('user_id')) {
//                 $query->where('user_id', $request->user_id);
//             }
//         } else {
//             $query->where('user_id', $user->id);
//         }
        
//           if ($user->role == 'adm' || $user->role == 'sadm') {
//             if ($request->filled('user_id')) {
//                 $query->where('user_id', $request->user_id);
//                 $filters['user_id'] = $request->user_id;
//             }
//         } else {
          
//             $query->where('user_id', $user->id);
//             $filters['user_id'] = $user->id;
//         }

      
//         if ($request->filled('from_date') && $request->filled('to_date')) {
//             $query->whereDate('created_at', '>=', $request->from_date)
//                   ->whereDate('created_at', '<=', $request->to_date);
//         } else {
//             $query->whereDate('created_at', Carbon::now()->toDateString());
//         }

//         $summary = $query->groupBy('status')->get();
//         $combined = $combined->merge($summary);
//     }


//     if ($combined->isEmpty()) {
//         return ResponseHelper::error('No transaction data found for the given criteria', 404);
//     }

//     $final = $combined->groupBy('status')->map(function ($group, $status) {
//         return [
//             'status'       => $status,
//             'total_txns'   => $group->sum('total_txns'),
//             'total_amount' => round($group->sum('total_amount'), 2),
//         ];
//     });

//     // ✅ Ensure default statuses always exist
//     $defaultStatuses = ['SUCCESS', 'FAILED', 'PENDING', 'REFUNDED'];
//     foreach ($defaultStatuses as $status) {
//         if (!isset($final[$status])) {
//             $final[$status] = [
//                 'status'       => $status,
//                 'total_txns'   => 0,
//                 'total_amount' => 0,
//             ];
//         }
//     }

//     // Re-index values
//     $final = collect($final)->values();

//     // Add overall totals
//     $final->push([
//         'status'       => 'total',
//         'total_txns'   => $final->sum('total_txns'),
//         'total_amount' => round($final->sum('total_amount'), 2),
//     ]);

//     return ResponseHelper::success('Retailer transaction summary', $final, 200);
// }
    public function getRetTxnSummarynew(Request $request)
    {
        $user = Auth::user();
    
        $services = [
            \App\Modules\Dmt\Models\DmtTxn::class,
            \App\Modules\Recharge\Models\RechargeTxn::class,
            \App\Modules\Payout\Models\PayoutTxn::class,
            \App\Modules\Aeps\Models\AepsTxn::class,
            \App\Modules\Bbps\Models\BbpsTxn::class,
            \App\Modules\Matm\Models\MatmTxn::class,
            \App\Modules\CreditCard\Models\CreditCard::class,
        ];
    
        $combined = collect();
    
        foreach ($services as $model) {
    
            $query = $model::select(
                'status',
                \DB::raw('COUNT(*) as total_txns'),
                \DB::raw('SUM(amount) as total_amount')
            );
    
            // ============================
            // USER FILTER LOGIC
            // ============================
    
            if (in_array($user->role, ['adm', 'sadm'])) {
    
                if ($request->filled('user_id')) {
    
                    $selectedUser = User::find($request->user_id);
    
                    // If DI selected → find all RET/DD under DI
                    if (in_array($selectedUser->role, ['di', 'dist'])) {
    
                        $childUsers = User::where('parent', $selectedUser->id)
                                          ->whereIn('role', ['ret', 'dd'])
                                          ->pluck('id')
                                          ->toArray();
    
                        if (!empty($childUsers)) {
                            $query->whereIn('user_id', $childUsers);
                        } else {
                            // no child users → force empty
                            $query->whereRaw('1=0');
                        }
                    }
                    // If directly RET or DD selected
                    elseif (in_array($selectedUser->role, ['ret', 'dd'])) {
                        $query->where('user_id', $selectedUser->id);
                    }
                    else {
                        // Any other role → no txns
                        $query->whereRaw('1=0');
                    }
                }
    
            } else {
                // Logged-in RET/DD
                $query->where('user_id', $user->id);
            }
    
            // ============================
            // DATE FILTER
            // ============================
    
            if ($request->filled('from_date') && $request->filled('to_date')) {
                $query->whereDate('created_at', '>=', $request->from_date)
                      ->whereDate('created_at', '<=', $request->to_date);
            } else {
                $query->whereDate('created_at', Carbon::now()->toDateString());
            }
    
            // ============================
            // GROUPING
            // ============================
    
            $summary = $query->groupBy('status')->get();
            $combined = $combined->merge($summary);
        }
    
        if ($combined->isEmpty()) {
            return ResponseHelper::error('No transaction data found for the given criteria', 404);
        }
    
        // ============================
        // FINAL MERGE
        // ============================
    
        $final = $combined->groupBy('status')->map(function ($group, $status) {
            return [
                'status'       => $status,
                'total_txns'   => $group->sum('total_txns'),
                'total_amount' => round($group->sum('total_amount'), 2),
            ];
        });
    
        // Default statuses
        $defaultStatuses = ['SUCCESS', 'FAILED', 'PENDING', 'REFUNDED'];
        foreach ($defaultStatuses as $status) {
            if (!isset($final[$status])) {
                $final[$status] = [
                    'status'       => $status,
                    'total_txns'   => 0,
                    'total_amount' => 0,
                ];
            }
        }
    
        $final = collect($final)->values();
    
        // Overall total
        $final->push([
            'status'       => 'TOTAL',
            'total_txns'   => $final->sum('total_txns'),
            'total_amount' => round($final->sum('total_amount'), 2),
        ]);
    
        return ResponseHelper::success('Retailer transaction summary', $final, 200);
    }
    public function getTxnSummaryServiceWise(Request $request)
    {
        $user = Auth::user();
    
        $services = [
            'DMT'        => \App\Modules\Dmt\Models\DmtTxn::class,
            'RECHARGE'   => \App\Modules\Recharge\Models\RechargeTxn::class,
            'PAYOUT'     => \App\Modules\Payout\Models\PayoutTxn::class,
            'AEPS'       => \App\Modules\Aeps\Models\AepsTxn::class,
            'BBPS'       => \App\Modules\Bbps\Models\BbpsTxn::class,
            'MATM'       => \App\Modules\Matm\Models\MatmTxn::class,
            'CREDITCARD' => \App\Modules\CreditCard\Models\CreditCard::class,
        ];
    
        $finalReport = collect();
    
        foreach ($services as $serviceName => $model) {
    
            $query = $model::select(
                'status',
                DB::raw('COUNT(*) as total_txns'),
                DB::raw('SUM(amount) as total_amount')
            );
    
            /* ============================
              USER FILTER (FIXED)
            ============================ */
    
            // ADMIN / SUPER ADMIN
            if (in_array($user->role, ['adm', 'sadm'])) {
    
                if ($request->filled('user_id')) {
                    $selectedUser = User::find($request->user_id);
    
                    if (in_array($selectedUser->role, ['di', 'md'])) {
    
                        // Get all RET + DD under MD/DI
                        $childUsers = User::where('parent', $selectedUser->id)
                            ->whereIn('role', ['ret', 'dd'])
                            ->pluck('id');
    
                        $query->whereIn('user_id', $childUsers);
    
                    } elseif (in_array($selectedUser->role, ['ret', 'dd'])) {
    
                        $query->where('user_id', $selectedUser->id);
    
                    } else {
                        $query->whereRaw('1=0');
                    }
                }
    
            }
            // MD / DI login → show all RET/DD under him
            elseif (in_array($user->role, ['md', 'di'])) {
    
                $childUsers = User::where('parent', $user->id)
                    ->whereIn('role', ['ret', 'dd'])
                    ->pluck('id');
    
                $query->whereIn('user_id', $childUsers);
            }
            // RET / DD login → show own + parent saved data
            else {
    
                $query->where(function ($q) use ($user) {
                    $q->where('user_id', $user->id)
                      ->orWhere('user_id', $user->parent);
                });
            }
    
            /* ============================
              DATE FILTER
            ============================ */
    
            if ($request->filled('from_date') && $request->filled('to_date')) {
                $query->whereDate('created_at', '>=', $request->from_date)
                      ->whereDate('created_at', '<=', $request->to_date);
            } 
    
            /* ============================
              GROUP BY STATUS
            ============================ */
    
            $rows = $query->groupBy('status')->get()->keyBy('status');
    
            $successAmount     = $rows['SUCCESS']->total_amount ?? 0;
            $successCount      = $rows['SUCCESS']->total_txns ?? 0;
    
            $failedAmount      = $rows['FAILED']->total_amount ?? 0;
            $failedCount       = $rows['FAILED']->total_txns ?? 0;
    
            $pendingAmount     = $rows['PENDING']->total_amount ?? 0;
            $pendingCount      = $rows['PENDING']->total_txns ?? 0;
    
            $processingAmount  = $rows['PROCESSING']->total_amount ?? 0;
            $processingCount   = $rows['PROCESSING']->total_txns ?? 0;
    
            $refundedAmount    = $rows['REFUNDED']->total_amount ?? 0;
            $refundedCount     = $rows['REFUNDED']->total_txns ?? 0;
    
            $totalAmount = $successAmount + $failedAmount + $pendingAmount + $processingAmount + $refundedAmount;
            $totalCount  = $successCount + $failedCount + $pendingCount + $processingCount + $refundedCount;
    
            $finalReport->push([
                'service_name'       => $serviceName,
    
                'success_amount'     => round($successAmount, 2),
                'success_count'      => $successCount,
    
                'failed_amount'      => round($failedAmount, 2),
                'failed_count'       => $failedCount,
    
                'pending_amount'     => round($pendingAmount, 2),
                'pending_count'      => $pendingCount,
    
                'processing_amount'  => round($processingAmount, 2),
                'processing_count'   => $processingCount,
    
                'refunded_amount'    => round($refundedAmount, 2),
                'refunded_count'     => $refundedCount,
    
                'total_amount'       => round($totalAmount, 2),
                'total_count'        => $totalCount,
            ]);
        }
    
        return ResponseHelper::success('Operator wise transaction report', $finalReport, 200);
    }
    public function getRetTxnSummary(Request $request)
    {
    $user = Auth::user();

    $services = [
        \App\Modules\Dmt\Models\DmtTxn::class,
        \App\Modules\Recharge\Models\RechargeTxn::class,
        \App\Modules\Payout\Models\PayoutTxn::class,
        \App\Modules\Aeps\Models\AepsTxn::class,
        \App\Modules\Bbps\Models\BbpsTxn::class,
        \App\Modules\Matm\Models\MatmTxn::class,
        \App\Modules\CreditCard\Models\CreditCard::class,
    ];

    $combined = collect();

    foreach ($services as $model) {
        $query = $model::select(
            'status',
            \DB::raw('COUNT(*) as total_txns'),
            \DB::raw('SUM(amount) as total_amount')
        );

     
        if ($user->role == 'adm' || $user->role == 'sadm') {
            if ($request->filled('user_id')) {
                $query->where('user_id', $request->user_id);
            }
        } else {
            $query->where('user_id', $user->id);
        }
        
          if ($user->role == 'adm' || $user->role == 'sadm') {
            if ($request->filled('user_id')) {
                $query->where('user_id', $request->user_id);
                $filters['user_id'] = $request->user_id;
            }
        } else {
          
            $query->where('user_id', $user->id);
            $filters['user_id'] = $user->id;
        }

      
        if ($request->filled('from_date') && $request->filled('to_date')) {
            $query->whereDate('created_at', '>=', $request->from_date)
                  ->whereDate('created_at', '<=', $request->to_date);
        } else {
            $query->whereDate('created_at', Carbon::now()->toDateString());
        }

        $summary = $query->groupBy('status')->get();
        $combined = $combined->merge($summary);
    }


    if ($combined->isEmpty()) {
        return ResponseHelper::error('No transaction data found for the given criteria', 404);
    }

    $final = $combined->groupBy('status')->map(function ($group, $status) {
        return [
            'status'       => $status,
            'total_txns'   => $group->sum('total_txns'),
            'total_amount' => round($group->sum('total_amount'), 2),
        ];
    });

    // ✅ Ensure default statuses always exist
    $defaultStatuses = ['SUCCESS', 'FAILED', 'PENDING', 'REFUNDED'];
    foreach ($defaultStatuses as $status) {
        if (!isset($final[$status])) {
            $final[$status] = [
                'status'       => $status,
                'total_txns'   => 0,
                'total_amount' => 0,
            ];
        }
    }

    // Re-index values
    $final = collect($final)->values();

    // Add overall totals
    $final->push([
        'status'       => 'total',
        'total_txns'   => $final->sum('total_txns'),
        'total_amount' => round($final->sum('total_amount'), 2),
    ]);

    return ResponseHelper::success('Retailer transaction summary', $final, 200);
}

    public function getUserServiceWiseTxnReport(Request $request)
    {
        if (!$request->filled('user_id')) {
            return ResponseHelper::success('User ID required', [], 200);
        }
    
        $user = User::find($request->user_id);
    
        if (!$user || !in_array($user->role, ['ret', 'dd', 'di', 'md'])) {
            return ResponseHelper::success('No report for this role', [], 200);
        }
    
        /*
        |--------------------------------------------------------------------------
        | Find All RET users based on hierarchy
        |--------------------------------------------------------------------------
        */
    
        $retUserIds = [];
    
        if ($user->role == 'ret') {
            $retUserIds[] = $user->id;
        }
    
        elseif ($user->role == 'di') {
            // all ret under this di
            $retUserIds = User::where('parent', $user->id)
                                ->where('role', 'ret')
                                ->pluck('id')
                                ->toArray();
        }
    
        elseif ($user->role == 'md') {
            // get all di under md
            $diIds = User::where('parent', $user->id)
                            ->where('role', 'di')
                            ->pluck('id');
    
            // get all ret under those di
            $retUserIds = User::whereIn('parent', $diIds)
                                ->where('role', 'ret')
                                ->pluck('id')
                                ->toArray();
        }
    
        if (empty($retUserIds)) {
            return ResponseHelper::success('No retailers found under this user', [], 200);
        }
    
        /*
        |--------------------------------------------------------------------------
        | Services
        |--------------------------------------------------------------------------
        */
    
        $services = [
            'DMT'        => \App\Modules\Dmt\Models\DmtTxn::class,
            'RECHARGE'   => \App\Modules\Recharge\Models\RechargeTxn::class,
            'PAYOUT'     => \App\Modules\Payout\Models\PayoutTxn::class,
            'AEPS'       => \App\Modules\Aeps\Models\AepsTxn::class,
            'BBPS'       => \App\Modules\Bbps\Models\BbpsTxn::class,
            'MATM'       => \App\Modules\Matm\Models\MatmTxn::class,
            'CREDITCARD' => \App\Modules\CreditCard\Models\CreditCard::class,
        ];
    
        $serviceColumns = [
            'DMT' => ['commission' => 'ret_comm', 'charges' => 'charges', 'tds' => ['ret_tds','di_tds','md_tds']],
            'RECHARGE' => ['commission' => 'ret_comm', 'charges' => null, 'tds' => ['ret_tds']],
            'PAYOUT' => ['commission' => 'ret_comm', 'charges' => 'charges', 'tds' => ['ret_tds','di_tds','md_tds']],
            'AEPS' => ['commission' => 'a_comm', 'charges' => null, 'tds' => []],
            'BBPS' => ['commission' => 'ret_comm', 'charges' => 'charges', 'tds' => ['ret_tds']],
            'MATM' => ['commission' => 'a_comm', 'charges' => null, 'tds' => []],
            'CREDITCARD' => ['commission' => null, 'charges' => null, 'tds' => []],
        ];
    
        $finalReport = [];
    
        /*
        |--------------------------------------------------------------------------
        | Loop Services
        |--------------------------------------------------------------------------
        */
    
        foreach ($services as $serviceName => $model) {
    
            $query = $model::whereIn('user_id', $retUserIds);
    
            if ($request->filled('from_date') && $request->filled('to_date')) {
                $query->whereDate('created_at', '>=', $request->from_date)
                      ->whereDate('created_at', '<=', $request->to_date);
            }
    
            $commissionCol = $serviceColumns[$serviceName]['commission'];
            $chargesCol    = $serviceColumns[$serviceName]['charges'];
            $tdsCols       = $serviceColumns[$serviceName]['tds'];
    
            $select = [
                'status',
                DB::raw('COUNT(*) as total_txns'),
                DB::raw('SUM(amount) as total_amount'),
            ];
    
            // Commission
            if ($commissionCol) {
                $select[] = DB::raw("SUM($commissionCol) as total_commission");
            } else {
                $select[] = DB::raw("0 as total_commission");
            }
    
            // Charges
            if ($chargesCol) {
                $select[] = DB::raw("SUM($chargesCol) as total_charges");
            } else {
                $select[] = DB::raw("0 as total_charges");
            }
    
            // TDS
            if (!empty($tdsCols)) {
                $select[] = DB::raw("SUM(" . implode(' + ', $tdsCols) . ") as total_tds");
            } else {
                $select[] = DB::raw("0 as total_tds");
            }
    
            $rows = $query->select($select)->groupBy('status')->get()->keyBy('status');
    
            $successAmount    = $rows['SUCCESS']->total_amount ?? 0;
            $successCount     = $rows['SUCCESS']->total_txns ?? 0;
    
            $failedAmount     = $rows['FAILED']->total_amount ?? 0;
            $failedCount      = $rows['FAILED']->total_txns ?? 0;
    
            $pendingAmount    = $rows['PENDING']->total_amount ?? 0;
            $pendingCount     = $rows['PENDING']->total_txns ?? 0;
    
            $processingAmount = $rows['PROCESSING']->total_amount ?? 0;
            $processingCount  = $rows['PROCESSING']->total_txns ?? 0;
    
            $refundedAmount   = $rows['REFUNDED']->total_amount ?? 0;
            $refundedCount    = $rows['REFUNDED']->total_txns ?? 0;
    
            $totalAmount = $successAmount + $failedAmount + $pendingAmount + $processingAmount + $refundedAmount;
            $totalCount  = $successCount + $failedCount + $pendingCount + $processingCount + $refundedCount;
    
            $commission = $rows->sum('total_commission');
            $charges    = $rows->sum('total_charges');
            $tds        = $rows->sum('total_tds');
    
            $finalReport[] = [
                'username'      => $user->name,
                'role'          => strtoupper($user->role),
                'service_name'  => $serviceName,
    
                'success_amount'    => round($successAmount,2),
                'success_count'     => $successCount,
    
                'failed_amount'     => round($failedAmount,2),
                'failed_count'      => $failedCount,
    
                'pending_amount'    => round($pendingAmount,2),
                'pending_count'     => $pendingCount,
    
                'processing_amount' => round($processingAmount,2),
                'processing_count'  => $processingCount,
    
                'refunded_amount'   => round($refundedAmount,2),
                'refunded_count'    => $refundedCount,
    
                'commission' => round($commission,2),
                'charges'    => round($charges,2),
                'tds'        => round($tds,2),
    
                'total_amount' => round($totalAmount,2),
                'total_count'  => $totalCount,
            ];
        }
    
        return ResponseHelper::success('User service wise transaction report', $finalReport, 200);
    }
// public function getUserServiceWiseTxnReport(Request $request)
// {
//     if (!$request->filled('user_id')) {
//         return ResponseHelper::success('User ID required', [], 200);
//     }

//     $user = User::find($request->user_id);

//     if (!$user || !in_array($user->role, ['ret', 'dd', 'di', 'md'])) {
//         return ResponseHelper::success('No report for this role', [], 200);
//     }

//     // ----------------------------
//     // Find all RET users under this hierarchy
//     // ----------------------------
//     $retUserIds = [];

//     if ($user->role == 'ret') {
//         $retUserIds[] = $user->id;
//     } elseif ($user->role == 'di') {
//         $retUserIds = User::where('parent', $user->id)
//                             ->where('role', 'ret')
//                             ->pluck('id')
//                             ->toArray();
//     } elseif ($user->role == 'md') {
//         $diIds = User::where('parent', $user->id)
//                         ->where('role', 'di')
//                         ->pluck('id')
//                         ->toArray();

//         if (!empty($diIds)) {
//             $retUserIds = User::whereIn('parent', $diIds)
//                                 ->where('role', 'ret')
//                                 ->pluck('id')
//                                 ->toArray();
//         }
//     }

//     if (empty($retUserIds)) {
//         return ResponseHelper::success('No retailers found under this user', [], 200);
//     }

//     // ----------------------------
//     // Services configuration
//     // ----------------------------
//     $services = [
//         'DMT'        => \App\Modules\Dmt\Models\DmtTxn::class,
//         'RECHARGE'   => \App\Modules\Recharge\Models\RechargeTxn::class,
//         'PAYOUT'     => \App\Modules\Payout\Models\PayoutTxn::class,
//         'AEPS'       => \App\Modules\Aeps\Models\AepsTxn::class,
//         'BBPS'       => \App\Modules\Bbps\Models\BbpsTxn::class,
//         'MATM'       => \App\Modules\Matm\Models\MatmTxn::class,
//         'CREDITCARD' => \App\Modules\CreditCard\Models\CreditCard::class,
//     ];

//     $serviceColumns = [
//         'DMT'        => ['commission' => 'ret_comm', 'charges' => 'charges', 'tds' => ['ret_tds','di_tds','md_tds']],
//         'RECHARGE'   => ['commission' => 'ret_comm', 'charges' => null, 'tds' => ['ret_tds']],
//         'PAYOUT'     => ['commission' => 'ret_comm', 'charges' => 'charges', 'tds' => ['ret_tds','di_tds','md_tds']],
//         'AEPS'       => ['commission' => 'a_comm', 'charges' => null, 'tds' => []],
//         'BBPS'       => ['commission' => 'ret_comm', 'charges' => 'charges', 'tds' => ['ret_tds']],
//         'MATM'       => ['commission' => 'a_comm', 'charges' => null, 'tds' => []],
//         'CREDITCARD' => ['commission' => null, 'charges' => null, 'tds' => []],
//     ];

//     $finalReport = [];

//     foreach ($services as $serviceName => $model) {

//         $query = $model::whereIn('user_id', $retUserIds)
//                         ->where('status', 'SUCCESS'); // Only SUCCESS transactions

//         if ($request->filled('from_date') && $request->filled('to_date')) {
//             $query->whereDate('created_at', '>=', $request->from_date)
//                   ->whereDate('created_at', '<=', $request->to_date);
//         }

//         $commissionCol = $serviceColumns[$serviceName]['commission'];
//         $chargesCol    = $serviceColumns[$serviceName]['charges'];
//         $tdsCols       = $serviceColumns[$serviceName]['tds'];

//         $selectRaw = "
//             COUNT(*) as total_txns,
//             SUM(amount) as total_amount,
//             " . ($commissionCol ? "SUM($commissionCol) as total_commission" : "0 as total_commission") . ",
//             " . ($chargesCol ? "SUM($chargesCol) as total_charges" : "0 as total_charges") . ",
//             " . (!empty($tdsCols) ? "SUM(" . implode(' + ', $tdsCols) . ") as total_tds" : "0 as total_tds") . "
//         ";

//         $totals = $query->selectRaw($selectRaw)->first();

//         $finalReport[] = [
//             'username'      => $user->name,
//             'role'          => strtoupper($user->role),
//             'service_name'  => $serviceName,

//             'success_amount'    => round($totals->total_amount, 2),
//             'success_count'     => $totals->total_txns,

//             'failed_amount'     => 0,
//             'failed_count'      => 0,

//             'pending_amount'    => 0,
//             'pending_count'     => 0,

//             'processing_amount' => 0,
//             'processing_count'  => 0,

//             'refunded_amount'   => 0,
//             'refunded_count'    => 0,

//             'commission' => round($totals->total_commission, 2),
//             'charges'    => round($totals->total_charges, 2),
//             'tds'        => round($totals->total_tds, 2),

//             'total_amount' => round($totals->total_amount, 2),
//             'total_count'  => $totals->total_txns,
//         ];
//     }

//     return ResponseHelper::success('User service wise transaction report', $finalReport, 200);
// }
// public function getServiceWiseProfitSummaryNew(Request $request){
//         $user = Auth::user();
    
//         // Date range filter (default to today)
//         $fromDate = $request->input('from_date', Carbon::now()->format('Y-m-d'));
//         $toDate   = $request->input('to_date', $fromDate);
    
//         // Base query
//         $query = \DB::table('service_summaries')
//             ->whereBetween('summary_date', [$fromDate, $toDate]);
    
//         $userIds = collect([$user->id]); // default self
    
//         switch ($user->role) {
//             case 'sadm':
//             case 'adm':
//                 // Admin sees all
//                 $query = $query;
//                 break;
    
//             case 'zsm':
//                 $asms = User::where('parent', $user->id)->pluck('id');
//                 $mds = User::whereIn('parent', $asms)->pluck('id');
//                 $distributors = User::whereIn('parent', $mds)->pluck('id');
//                 $retailers = User::whereIn('parent', $distributors)->pluck('id');
//                 $userIds = $userIds->merge($asms)->merge($mds)->merge($distributors)->merge($retailers)->unique();
//                 $query = $query->whereIn('user_id', $userIds);
//                 break;
    
//             case 'asm':
//                 $mds = User::where('parent', $user->id)->pluck('id');
//                 $distributors = User::whereIn('parent', $mds)->pluck('id');
//                 $retailers = User::whereIn('parent', $distributors)->pluck('id');
//                 $userIds = $userIds->merge($mds)->merge($distributors)->merge($retailers)->unique();
//                 $query = $query->whereIn('user_id', $userIds);
//                 break;
    
//             case 'md':
//                 $distributors = User::where('parent', $user->id)->pluck('id');
//                 $retailers = User::whereIn('parent', $distributors)->pluck('id');
//                 $userIds = $userIds->merge($distributors)->merge($retailers)->unique();
//                 $query = $query->whereIn('user_id', $userIds);
//                 break;
    
//             case 'di':
//                 $retailers = User::where('parent', $user->id)->pluck('id');
//                 $userIds = $userIds->merge($retailers)->unique();
//                 $query = $query->whereIn('user_id', $userIds);
//                 break;
    
//             case 'ret':
//             default:
//                 $query = $query->where('user_id', $user->id);
//                 break;
//         }
    
//         // Fetch summary grouped by service
//         $servicesRaw = $query
//             ->select('service_name', 
//                      \DB::raw('SUM(total_amount) as total_amount'),
//                      \DB::raw('SUM(a_comm) as a_comm'),
//                      \DB::raw('SUM(total_comm) as total_comm'),
//                      \DB::raw('SUM(total_tds) as total_tds'),
//                      \DB::raw('SUM(di_comm) as di_comm'),
//                      \DB::raw('SUM(di_tds) as di_tds'),
//                      \DB::raw('SUM(md_comm) as md_comm'),
//                      \DB::raw('SUM(md_tds) as md_tds'),
//                      \DB::raw('SUM(total_charges) as total_charges'))
//             ->groupBy('service_name')
//             ->get();
    
//         // Initialize response structure
//         $services = [];
//         $grandTotal = [
//             'total_amount' => 0,
//             'a_comm'   => 0,
//             'total_comm'   => 0,
//             'total_tds'   => 0,
//             'di_comm'   => 0,
//             'di_tds'   => 0,
//             'md_comm'   => 0,
//             'md_tds'   => 0,
//             'total_charges'=> 0,
//             'profit'       => 0,
//         ];
    
//         foreach ($servicesRaw as $row) {
//             $amount = round($row->total_amount, 2);
//             $a_comm   = round($row->a_comm, 2);
//             $comm   = round($row->total_comm, 2);
//             $total_tds   = round($row->total_tds, 2);
//             $di_comm   = round($row->di_comm, 2);
//             $di_tds   = round($row->di_tds, 2);
//             $md_comm   = round($row->md_comm, 2);
//             $md_tds   = round($row->md_tds, 2);
//             $charges= round($row->total_charges, 2);
//             $profit = $comm - $charges;
    
//             $services[$row->service_name] = [
//                 'total_amount'  => $amount,
//                 'a_comm'  => $a_comm,
//                 'total_comm'    => $comm,
//                 'total_tds'   => $total_tds,
//                 'di_comm'   => $di_comm,
//                 'di_tds'   => $di_tds,
//                 'md_comm'   => $md_comm,
//                 'md_tds'   => $md_tds,
//                 'total_charges' => $charges,
//                 'profit'        => $profit,
//             ];
    
//             $grandTotal['total_amount'] += $amount;
//             $grandTotal['a_comm']   += $a_comm;
//             $grandTotal['total_comm']   += $comm;
//             $grandTotal['total_tds']   += $total_tds;
//             $grandTotal['di_comm']   += $di_comm;
//             $grandTotal['di_tds']   += $di_tds;
//             $grandTotal['md_comm']   += $md_comm;
//             $grandTotal['md_tds']   += $md_tds;
//             $grandTotal['total_charges']+= $charges;
//             $grandTotal['profit']       += $profit;
//         }
    
//         // Round grand totals
//         foreach ($grandTotal as $key => $value) {
//             $grandTotal[$key] = round($value, 2);
//         }
    
//         return ResponseHelper::success('Service wise profit summary', [
//             'services'    => $services,
//             'grand_total' => $grandTotal,
//         ], 200);
//     }
// }
public function getServiceWiseProfitSummaryNew(Request $request){
    $user = Auth::user();

    $fromDate = $request->input('from_date', Carbon::now()->format('Y-m-d'));
    $toDate   = $request->input('to_date', $fromDate);

    $userIds = collect([$user->id]);

    switch ($user->role) {
        case 'sadm':
        case 'adm':
            break;

        case 'zsm':
            $asms = User::where('parent', $user->id)->pluck('id');
            $mds = User::whereIn('parent', $asms)->pluck('id');
            $distributors = User::whereIn('parent', $mds)->pluck('id');
            $retailers = User::whereIn('parent', $distributors)->pluck('id');
            $userIds = $userIds->merge($asms)->merge($mds)->merge($distributors)->merge($retailers)->unique();
            break;

        case 'asm':
            $mds = User::where('parent', $user->id)->pluck('id');
            $distributors = User::whereIn('parent', $mds)->pluck('id');
            $retailers = User::whereIn('parent', $distributors)->pluck('id');
            $userIds = $userIds->merge($mds)->merge($distributors)->merge($retailers)->unique();
            break;

        case 'md':
            $distributors = User::where('parent', $user->id)->pluck('id');
            $retailers = User::whereIn('parent', $distributors)->pluck('id');
            $userIds = $userIds->merge($distributors)->merge($retailers)->unique();
            break;

        case 'di':
            $retailers = User::where('parent', $user->id)->pluck('id');
            $userIds = $userIds->merge($retailers)->unique();
            break;

        case 'ret':
        default:
            break;
    }

    /*
    |--------------------------------------------------------------------------
    | 1️⃣ SERVICE SUMMARIES DATA
    |--------------------------------------------------------------------------
    */
    $serviceData = \DB::table('service_summaries')
        ->whereBetween('summary_date', [$fromDate, $toDate])
        ->when($user->role != 'sadm' && $user->role != 'adm', function ($q) use ($userIds) {
            return $q->whereIn('user_id', $userIds);
        })
        ->select(
            'service_name',
            \DB::raw('SUM(total_amount) as total_amount'),
            \DB::raw('SUM(a_comm) as a_comm'),
            \DB::raw('SUM(total_comm) as total_comm'),
            \DB::raw('SUM(total_tds) as total_tds'),
            \DB::raw('SUM(di_comm) as di_comm'),
            \DB::raw('SUM(di_tds) as di_tds'),
            \DB::raw('SUM(md_comm) as md_comm'),
            \DB::raw('SUM(md_tds) as md_tds'),
            \DB::raw('SUM(total_charges) as total_charges')
        )
        ->groupBy('service_name')
        ->get();

    /*
    |--------------------------------------------------------------------------
    | 2️⃣ COLLECTION DATA
    |--------------------------------------------------------------------------
    */
    
    $collectionData = \DB::table('collections2')
        ->whereBetween(\DB::raw('DATE(created_at)'), [$fromDate, $toDate])
        ->when($user->role != 'sadm' && $user->role != 'adm', function ($q) use ($userIds) {
            return $q->whereIn('user_id', $userIds);
        })
        ->select(
            \DB::raw("'COLLECTION' as service_name"),
            \DB::raw('SUM(IFNULL(amount,0)) as total_amount'),
            \DB::raw('SUM(IFNULL(a_comm,0)) as a_comm'),
      \DB::raw('SUM(IFNULL(ret_comm,0)) as total_comm'),
            \DB::raw('SUM(IFNULL(ret_tds,0) + IFNULL(di_tds,0) + IFNULL(md_tds,0)) as total_tds'),
            \DB::raw('SUM(IFNULL(di_comm,0)) as di_comm'),
            \DB::raw('SUM(IFNULL(di_tds,0)) as di_tds'),
            \DB::raw('SUM(IFNULL(md_comm,0)) as md_comm'),
            \DB::raw('SUM(IFNULL(md_tds,0)) as md_tds'),
            \DB::raw('SUM(IFNULL(charges,0)) as total_charges')
        )
        ->get();
    /*
    |--------------------------------------------------------------------------
    | 3️⃣ COLLECTION3 DATA
    |--------------------------------------------------------------------------
    */
    $collection3Data = \DB::table('collections3')
        ->where('status', 'CLAIMED') 
        ->whereBetween(\DB::raw('DATE(created_at)'), [$fromDate, $toDate])
        ->when($user->role != 'sadm' && $user->role != 'adm', function ($q) use ($userIds) {
            return $q->whereIn('user_id', $userIds);
        })
        ->select(
            \DB::raw("'COLLECTION3' as service_name"),
            \DB::raw('SUM(IFNULL(amount,0)) as total_amount'),
            \DB::raw('SUM(IFNULL(a_comm,0)) as a_comm'),
            \DB::raw('SUM(IFNULL(ret_comm,0)) as total_comm'),
            \DB::raw('SUM(IFNULL(ret_tds,0) + IFNULL(di_tds,0) + IFNULL(md_tds,0)) as total_tds'),
            \DB::raw('SUM(IFNULL(di_comm,0)) as di_comm'),
            \DB::raw('SUM(IFNULL(di_tds,0)) as di_tds'),
            \DB::raw('SUM(IFNULL(md_comm,0)) as md_comm'),
            \DB::raw('SUM(IFNULL(md_tds,0)) as md_tds'),
            \DB::raw('SUM(IFNULL(charges,0)) as total_charges')
        )
        ->get();
    $allData = $serviceData->merge($collectionData)->merge($collection3Data);;

    /*
    |--------------------------------------------------------------------------
    | FORMAT SAME AS BEFORE
    |--------------------------------------------------------------------------
    */
    $services = [];
    $grandTotal = [
        'total_amount' => 0,
        'a_comm' => 0,
        'total_comm' => 0,
        'total_tds' => 0,
        'di_comm' => 0,
        'di_tds' => 0,
        'md_comm' => 0,
        'md_tds' => 0,
        'total_charges'=> 0,
        'profit' => 0,
    ];

    foreach ($allData as $row) {

        $profit = $row->total_comm - $row->total_charges;

        $services[$row->service_name] = [
            'total_amount'  => round($row->total_amount, 2),
            'a_comm'  => round($row->a_comm, 2),
            'total_comm'    => round($row->total_comm, 2),
            'total_tds'   => round($row->total_tds, 2),
            'di_comm'   => round($row->di_comm, 2),
            'di_tds'   => round($row->di_tds, 2),
            'md_comm'   => round($row->md_comm, 2),
            'md_tds'   => round($row->md_tds, 2),
            'total_charges' => round($row->total_charges, 2),
            'profit'        => round($profit, 2),
        ];

        $grandTotal['total_amount'] += $row->total_amount;
        $grandTotal['a_comm'] += $row->a_comm;
        $grandTotal['total_comm'] += $row->total_comm;
        $grandTotal['total_tds'] += $row->total_tds;
        $grandTotal['di_comm'] += $row->di_comm;
        $grandTotal['di_tds'] += $row->di_tds;
        $grandTotal['md_comm'] += $row->md_comm;
        $grandTotal['md_tds'] += $row->md_tds;
        $grandTotal['total_charges'] += $row->total_charges;
        $grandTotal['profit'] += $profit;
    }

    foreach ($grandTotal as $key => $value) {
        $grandTotal[$key] = round($value, 2);
    }

    return ResponseHelper::success('Service wise profit summary', [
        'services'    => $services,
        'grand_total' => $grandTotal,
    ], 200);
}




}

// namespace App\Modules\Dashboard\Controllers;
// use Laravel\Lumen\Routing\Controller as BaseController;
// use Illuminate\Http\Request;
// use DB;
// use App\Helper\ResponseHelper;
// use Illuminate\Support\Facades\Crypt;
// use App\Modules\User\Models\User;
// use Carbon\Carbon;
// use Auth;


// class DashboardController extends BaseController
// {
    
//     public function __construct(){
//         $this->middleware('auth', ['except' => []]);
//         $this->middleware('check.active', ['except' => []]);
//     }
//     public function getRoleWiseCounts(){
//         if(Auth::user()->role != 'adm' && Auth::user()->role != 'sadm'){
//             return ResponseHelper::error('Unauthorised Access', '', 403);
//         }
//         $allowedRoles = ['md', 'di', 'ret', 'dd', 'api','adm'];
//         $users = User::whereIn('role', $allowedRoles)
//                      ->get(['id', 'role', 'w1', 'w2', 'w3']);
//         $result = $users->groupBy('role')->map(function ($group, $role) {
//             return [
//                 'role'        => $role,
//                 'total_users' => $group->count(),
//                 'total_w1'    => $group->sum('w1')/100,
//                 'total_w2'    => $group->sum('w2')/100,
//                 'total_w3'    => $group->sum('w3')/100,
//             ];
//         })->values();
//         return ResponseHelper::success('Role-wise counts', $result, 200);
//     }
    
//     public function getServiceWiseSummary(){
//         if(Auth::user()->role != 'adm' && Auth::user()->role != 'sadm'){
//             return ResponseHelper::error('Unauthorised Access', '', 403);
//         }
//         $services = [
//             'dmt'      => \App\Modules\Dmt\Models\DmtTxn::class,
//             'recharge' => \App\Modules\Recharge\Models\RechargeTxn::class,
//             'payout'   => \App\Modules\Payout\Models\PayoutTxn::class,
//             'aeps'     => \App\Modules\Aeps\Models\AepsTxn::class,
//             'bbps'     => \App\Modules\Bbps\Models\BbpsTxn::class,
//             'matm'     => \App\Modules\Matm\Models\MatmTxn::class,
//             'Credit Card'     => \App\Modules\CreditCard\Models\CreditCard::class,
//             // add more services here
//         ];
//         $today          = \Carbon\Carbon::today();
//         $startOfMonth   = \Carbon\Carbon::now()->startOfMonth();
//         $endOfMonth     = \Carbon\Carbon::now()->endOfMonth();
//         $lastMonthStart = \Carbon\Carbon::now()->subMonth()->startOfMonth();
//         $lastMonthEnd   = \Carbon\Carbon::now()->subMonth()->endOfMonth();
    
//         $result = [];
    
//         foreach ($services as $serviceName => $model) {
//             $summary = $model::where('status', 'SUCCESS')->orWhere('status', 'PENDING')
//                 ->selectRaw('COUNT(*) as total_txns')
//                 ->selectRaw('SUM(amount) as total_amount')
//                 ->selectRaw("SUM(CASE WHEN DATE(created_at) = ? THEN amount ELSE 0 END) as td_amount", [$today])
//                 ->selectRaw("COUNT(CASE WHEN DATE(created_at) = ? THEN 1 END) as td_count", [$today])
//                 ->selectRaw("SUM(CASE WHEN created_at BETWEEN ? AND ? THEN amount ELSE 0 END) as mtd_amount", [$startOfMonth, $endOfMonth])
//                 ->selectRaw("COUNT(CASE WHEN created_at BETWEEN ? AND ? THEN 1 END) as mtd_count", [$startOfMonth, $endOfMonth])
//                 ->selectRaw("SUM(CASE WHEN created_at BETWEEN ? AND ? THEN amount ELSE 0 END) as lmtd_amount", [$lastMonthStart, $lastMonthEnd])
//                 ->selectRaw("COUNT(CASE WHEN created_at BETWEEN ? AND ? THEN 1 END) as lmtd_count", [$lastMonthStart, $lastMonthEnd])
//                 ->first();
    
//             $result[$serviceName] = $summary;
//         }
    
//         return ResponseHelper::success('Service wise transaction summary', $result, 200);
//     }
    
//     public function getRetTxnSummary(Request $request)
//     {
//     $user = Auth::user();

//     $services = [
//         \App\Modules\Dmt\Models\DmtTxn::class,
//         \App\Modules\Recharge\Models\RechargeTxn::class,
//         \App\Modules\Payout\Models\PayoutTxn::class,
//         \App\Modules\Aeps\Models\AepsTxn::class,
//         \App\Modules\Bbps\Models\BbpsTxn::class,
//         \App\Modules\Matm\Models\MatmTxn::class,
//         \App\Modules\CreditCard\Models\CreditCard::class,
//     ];

//     $combined = collect();

//     foreach ($services as $model) {
//         $query = $model::select(
//             'status',
//             \DB::raw('COUNT(*) as total_txns'),
//             \DB::raw('SUM(amount) as total_amount')
//         );

     
//         if ($user->role == 'adm' || $user->role == 'sadm') {
//             if ($request->filled('user_id')) {
//                 $query->where('user_id', $request->user_id);
//             }
//         } else {
//             $query->where('user_id', $user->id);
//         }
        
//           if ($user->role == 'adm' || $user->role == 'sadm') {
//             if ($request->filled('user_id')) {
//                 $query->where('user_id', $request->user_id);
//                 $filters['user_id'] = $request->user_id;
//             }
//         } else {
          
//             $query->where('user_id', $user->id);
//             $filters['user_id'] = $user->id;
//         }

      
//         if ($request->filled('from_date') && $request->filled('to_date')) {
//             $query->whereDate('created_at', '>=', $request->from_date)
//                   ->whereDate('created_at', '<=', $request->to_date);
//         } else {
//             $query->whereDate('created_at', Carbon::now()->toDateString());
//         }

//         $summary = $query->groupBy('status')->get();
//         $combined = $combined->merge($summary);
//     }


//     if ($combined->isEmpty()) {
//         return ResponseHelper::error('No transaction data found for the given criteria', 404);
//     }

//     $final = $combined->groupBy('status')->map(function ($group, $status) {
//         return [
//             'status'       => $status,
//             'total_txns'   => $group->sum('total_txns'),
//             'total_amount' => round($group->sum('total_amount'), 2),
//         ];
//     });

//     // ✅ Ensure default statuses always exist
//     $defaultStatuses = ['SUCCESS', 'FAILED', 'PENDING', 'REFUNDED'];
//     foreach ($defaultStatuses as $status) {
//         if (!isset($final[$status])) {
//             $final[$status] = [
//                 'status'       => $status,
//                 'total_txns'   => 0,
//                 'total_amount' => 0,
//             ];
//         }
//     }

//     // Re-index values
//     $final = collect($final)->values();

//     // Add overall totals
//     $final->push([
//         'status'       => 'total',
//         'total_txns'   => $final->sum('total_txns'),
//         'total_amount' => round($final->sum('total_amount'), 2),
//     ]);

//     return ResponseHelper::success('Retailer transaction summary', $final, 200);
// }
    
//     public function getServiceWiseProfitSummaryNew(Request $request){
//         $user = Auth::user();
    
//         // Date range filter (default to today)
//         $fromDate = $request->input('from_date', Carbon::now()->format('Y-m-d'));
//         $toDate   = $request->input('to_date', $fromDate);
    
//         // Base query
//         $query = \DB::table('service_summaries')
//             ->whereBetween('summary_date', [$fromDate, $toDate]);
    
//         $userIds = collect([$user->id]); // default self
    
//         switch ($user->role) {
//             case 'sadm':
//             case 'adm':
//                 // Admin sees all
//                 $query = $query;
//                 break;
    
//             case 'zsm':
//                 $asms = User::where('parent', $user->id)->pluck('id');
//                 $mds = User::whereIn('parent', $asms)->pluck('id');
//                 $distributors = User::whereIn('parent', $mds)->pluck('id');
//                 $retailers = User::whereIn('parent', $distributors)->pluck('id');
//                 $userIds = $userIds->merge($asms)->merge($mds)->merge($distributors)->merge($retailers)->unique();
//                 $query = $query->whereIn('user_id', $userIds);
//                 break;
    
//             case 'asm':
//                 $mds = User::where('parent', $user->id)->pluck('id');
//                 $distributors = User::whereIn('parent', $mds)->pluck('id');
//                 $retailers = User::whereIn('parent', $distributors)->pluck('id');
//                 $userIds = $userIds->merge($mds)->merge($distributors)->merge($retailers)->unique();
//                 $query = $query->whereIn('user_id', $userIds);
//                 break;
    
//             case 'md':
//                 $distributors = User::where('parent', $user->id)->pluck('id');
//                 $retailers = User::whereIn('parent', $distributors)->pluck('id');
//                 $userIds = $userIds->merge($distributors)->merge($retailers)->unique();
//                 $query = $query->whereIn('user_id', $userIds);
//                 break;
    
//             case 'di':
//                 $retailers = User::where('parent', $user->id)->pluck('id');
//                 $userIds = $userIds->merge($retailers)->unique();
//                 $query = $query->whereIn('user_id', $userIds);
//                 break;
    
//             case 'ret':
//             default:
//                 $query = $query->where('user_id', $user->id);
//                 break;
//         }
    
//         // Fetch summary grouped by service
//         $servicesRaw = $query
//             ->select('service_name', 
//                      \DB::raw('SUM(total_amount) as total_amount'),
//                      \DB::raw('SUM(a_comm) as a_comm'),
//                      \DB::raw('SUM(total_comm) as total_comm'),
//                      \DB::raw('SUM(total_tds) as total_tds'),
//                      \DB::raw('SUM(di_comm) as di_comm'),
//                      \DB::raw('SUM(di_tds) as di_tds'),
//                      \DB::raw('SUM(md_comm) as md_comm'),
//                      \DB::raw('SUM(md_tds) as md_tds'),
//                      \DB::raw('SUM(total_charges) as total_charges'))
//             ->groupBy('service_name')
//             ->get();
    
//         // Initialize response structure
//         $services = [];
//         $grandTotal = [
//             'total_amount' => 0,
//             'a_comm'   => 0,
//             'total_comm'   => 0,
//             'total_tds'   => 0,
//             'di_comm'   => 0,
//             'di_tds'   => 0,
//             'md_comm'   => 0,
//             'md_tds'   => 0,
//             'total_charges'=> 0,
//             'profit'       => 0,
//         ];
    
//         foreach ($servicesRaw as $row) {
//             $amount = round($row->total_amount, 2);
//             $a_comm   = round($row->a_comm, 2);
//             $comm   = round($row->total_comm, 2);
//             $total_tds   = round($row->total_tds, 2);
//             $di_comm   = round($row->di_comm, 2);
//             $di_tds   = round($row->di_tds, 2);
//             $md_comm   = round($row->md_comm, 2);
//             $md_tds   = round($row->md_tds, 2);
//             $charges= round($row->total_charges, 2);
//             $profit = $comm - $charges;
    
//             $services[$row->service_name] = [
//                 'total_amount'  => $amount,
//                 'a_comm'  => $a_comm,
//                 'total_comm'    => $comm,
//                 'total_tds'   => $total_tds,
//                 'di_comm'   => $di_comm,
//                 'di_tds'   => $di_tds,
//                 'md_comm'   => $md_comm,
//                 'md_tds'   => $md_tds,
//                 'total_charges' => $charges,
//                 'profit'        => $profit,
//             ];
    
//             $grandTotal['total_amount'] += $amount;
//             $grandTotal['a_comm']   += $a_comm;
//             $grandTotal['total_comm']   += $comm;
//             $grandTotal['total_tds']   += $total_tds;
//             $grandTotal['di_comm']   += $di_comm;
//             $grandTotal['di_tds']   += $di_tds;
//             $grandTotal['md_comm']   += $md_comm;
//             $grandTotal['md_tds']   += $md_tds;
//             $grandTotal['total_charges']+= $charges;
//             $grandTotal['profit']       += $profit;
//         }
    
//         // Round grand totals
//         foreach ($grandTotal as $key => $value) {
//             $grandTotal[$key] = round($value, 2);
//         }
    
//         return ResponseHelper::success('Service wise profit summary', [
//             'services'    => $services,
//             'grand_total' => $grandTotal,
//         ], 200);
//     }
// }
