<?php

namespace App\Modules\Admin\Controllers;

use App\Modules\Admin\Models\Route;
use App\Helper\ResponseHelper;
use Illuminate\Http\Request;
use App\Modules\User\Models\User;
use DB;
use Auth;

use Laravel\Lumen\Routing\Controller as BaseController;

class AdminController extends BaseController
{
    
    public function __construct(){
        $this->middleware('auth', ['except' => ['']]);
        $this->middleware('check.active', ['except' => '']);
    }
    
    public function getRoutes(){
        $routes = Route::select('name','code')->where('status', 1)->get();
        return ResponseHelper::success('Routes fetched', $routes, 200);
    }
    
//     public function getAllTxn(Request $request)
//     {
//         $user = Auth::user();
    
//         // Common column set (13 total)
//         $commonCols = [
//             'txn_id',
//             'client_ref',
//             'user_id',
//             DB::raw('operator as operator_name'),
//             'operator_id',
//             'route',
//             'amount',
//             'ret_comm',
//             'di_comm',
//             'md_comm',
//             'a_comm',
//             'charges',
//             'status',
//             DB::raw("CONVERT_TZ(created_at, '+00:00', '+05:30') as created_at"),
//         ];
    
//         // Individual service queries (each must return 14 columns: service_name + commonCols)
    
//         $bbps = DB::table('bbps_txns')->select([
//             DB::raw("'BBPS' as service_name"), ...$commonCols
//         ]);
        
//         $verif = DB::table('verification_txns')->select([
//             DB::raw("'VERIFICATION' as service_name"), ...$commonCols
//         ]);
    
//         $dmt = DB::table('dmt_txns')->select([
//             DB::raw("'DMT' as service_name"), ...$commonCols
//         ]);
    
//         $payout = DB::table('payout_txns')->select([
//             DB::raw("'PAYOUT' as service_name"), ...$commonCols
//         ]);
//         $aeps = DB::table('aeps_txns')->select([
//             DB::raw("'AEPS' as service_name"),
//             'txn_id',
//             'client_ref',
//             'user_id',
//             DB::raw('operator as operator_name'),
//             DB::raw('rrn as operator_id'),
//             'route',
//             'amount',
//             'ret_comm',
//             'di_comm',
//             'md_comm',
//             'a_comm',
//             DB::raw('0 as charges'),
//             'status',
//             DB::raw("CONVERT_TZ(created_at, '+00:00', '+05:30') as created_at"),
//         ]);
    
//         $recharge = DB::table('recharge_txns')->select([
//             DB::raw("'RECHARGE' as service_name"),
//             'txn_id',
//             'client_ref',
//             'user_id',
//             DB::raw('operator as operator_name'),
//             'operator_id',
//             'route',
//             'amount',
//             'ret_comm',
//             'di_comm',
//             'md_comm',
//             'a_comm',
//             DB::raw('0 as charges'),
//             'status',
//             DB::raw("CONVERT_TZ(created_at, '+00:00', '+05:30') as created_at"),
//         ]);
    
//         $wallet = DB::table('wallet_transactions')->select([
//             DB::raw("'WALLET' as service_name"),
//             'txn_id',
//             'client_ref',
//             'user_id',
//             DB::raw('operator as operator_name'),
//             DB::raw('0 as operator_id'),
//             'route',
//             'amount',
//             DB::raw('0 as ret_comm'),
//             DB::raw('0 as di_comm'),
//             DB::raw('0 as md_comm'),
//             DB::raw('0 as a_comm'),
//             DB::raw('charge as charges'),
//             'status',
//             DB::raw("CONVERT_TZ(created_at, '+00:00', '+05:30') as created_at"),
//         ]);
    
//         // Combine with unionAll — all have exactly 14 columns
//         $unionQuery = $aeps
//             ->unionAll($bbps)
//             ->unionAll($dmt)
//             ->unionAll($payout)
//             ->unionAll($recharge)
//             ->unionAll($wallet)
//             ->unionAll($verif);
    
//         $query = DB::query()->fromSub($unionQuery, 'all_txns')->orderByDesc('created_at');
    
//         //  Role-based filtering
//         if ($user->role === 'adm' || $user->role === 'sadm') {
//             // Admins see everything
//         } elseif ($user->role === 'md') {
//             $userIds = User::getDescendantUserIds($user->id);
//             $userIds = array_merge([$user->id], $userIds ?? []);
//             $query->whereIn('user_id', $userIds);
//         } elseif ($user->role === 'di') {
//             $userIds = User::getDescendantUserIds($user->id);
//             $userIds = array_merge([$user->id], $userIds ?? []);
//             $query->whereIn('user_id', $userIds);
//         } else {
//             $query->where('user_id', $user->id);
//         }
    
//         //  Apply filters
//         $query->when($request->filled('user_id'), fn($q) =>
//             $q->where('user_id', $request->user_id)
//         )
//         ->when($request->filled('txn_id'), fn($q) =>
//             $q->where('txn_id', $request->txn_id)
//         )
//         ->when($request->filled('status'), fn($q) =>
//             $q->where('status', strtoupper($request->status))
//         )
//          ->when($request->filled('route'), fn($q) =>
//             $q->where('route', strtoupper($request->route))
//         )
//         ->when($request->filled('service_name'), fn($q) =>
//             $q->where('service_name', strtoupper(str_replace('+', ' ', $request->service_name)))
//         )
//         ->when($request->filled('from_date') && $request->filled('to_date'), fn($q) =>
//             $q->whereBetween('created_at', [
//                 $request->from_date . ' 00:00:00',
//                 $request->to_date . ' 23:59:59'
//             ])
//         );
//       if ($request->input('export') == 1) {
//     $transactions = $query->get();
//     return ResponseHelper::success('Transactions fetched successfully', $transactions, 200);
// }
//         //  Pagination
//         $transactions = $query->paginate($request->input('paginate', 15));
    
//         return ResponseHelper::success('Transactions fetched successfully', $transactions, 200);
//     }
public function getAllTxn(Request $request)
{
    $user = Auth::user();

    // Common column set (now includes updated_at)
    $commonCols = [
        'txn_id',
        'client_ref',
        'user_id',
        DB::raw('operator as operator_name'),
        'operator_id',
        'route',
        'amount',
        'ret_comm',
        'di_comm',
        'md_comm',
        'a_comm',
        'charges',
        'status',
        DB::raw("CONVERT_TZ(created_at, '+00:00', '+05:30') as created_at"),
        DB::raw("CONVERT_TZ(updated_at, '+00:00', '+05:30') as updated_at"),
    ];

    // Individual service queries

    $bbps = DB::table('bbps_txns')->select([
        DB::raw("'BBPS' as service_name"), ...$commonCols
    ]);
    
    $verif = DB::table('verification_txns')->select([
        DB::raw("'VERIFICATION' as service_name"), ...$commonCols
    ]);

    $dmt = DB::table('dmt_txns')->select([
        DB::raw("'DMT' as service_name"), ...$commonCols
    ]);

    $payout = DB::table('payout_txns')->select([
        DB::raw("'PAYOUT' as service_name"), ...$commonCols
    ]);

    $aeps = DB::table('aeps_txns')->select([
        DB::raw("'AEPS' as service_name"),
        'txn_id',
        'client_ref',
        'user_id',
        DB::raw('operator as operator_name'),
        DB::raw('rrn as operator_id'),
        'route',
        'amount',
        'ret_comm',
        'di_comm',
        'md_comm',
        'a_comm',
        DB::raw('0 as charges'),
        'status',
        DB::raw("CONVERT_TZ(created_at, '+00:00', '+05:30') as created_at"),
        DB::raw("CONVERT_TZ(updated_at, '+00:00', '+05:30') as updated_at"),
    ]);

    $recharge = DB::table('recharge_txns')->select([
        DB::raw("'RECHARGE' as service_name"),
        'txn_id',
        'client_ref',
        'user_id',
        DB::raw('operator as operator_name'),
        'operator_id',
        'route',
        'amount',
        'ret_comm',
        'di_comm',
        'md_comm',
        'a_comm',
        DB::raw('0 as charges'),
        'status',
        DB::raw("CONVERT_TZ(created_at, '+00:00', '+05:30') as created_at"),
        DB::raw("CONVERT_TZ(updated_at, '+00:00', '+05:30') as updated_at"),
    ]);

    $wallet = DB::table('wallet_transactions')->select([
        DB::raw("'WALLET' as service_name"),
        'txn_id',
        'client_ref',
        'user_id',
        DB::raw('operator as operator_name'),
        DB::raw('0 as operator_id'),
        'route',
        'amount',
        DB::raw('0 as ret_comm'),
        DB::raw('0 as di_comm'),
        DB::raw('0 as md_comm'),
        DB::raw('0 as a_comm'),
        DB::raw('charge as charges'),
        'status',
        DB::raw("CONVERT_TZ(created_at, '+00:00', '+05:30') as created_at"),
        DB::raw("CONVERT_TZ(updated_at, '+00:00', '+05:30') as updated_at"),
    ]);

    // Combine with unionAll
    $unionQuery = $aeps
        ->unionAll($bbps)
        ->unionAll($dmt)
        ->unionAll($payout)
        ->unionAll($recharge)
        ->unionAll($wallet)
        ->unionAll($verif);

    $query = DB::query()->fromSub($unionQuery, 'all_txns')->orderByDesc('created_at');

    // Role-based filtering
    if ($user->role === 'adm' || $user->role === 'sadm') {
        // Admins see everything
    } elseif ($user->role === 'md') {
        $userIds = User::getDescendantUserIds($user->id);
        $userIds = array_merge([$user->id], $userIds ?? []);
        $query->whereIn('user_id', $userIds);
    } elseif ($user->role === 'di') {
        $userIds = User::getDescendantUserIds($user->id);
        $userIds = array_merge([$user->id], $userIds ?? []);
        $query->whereIn('user_id', $userIds);
    } else {
        $query->where('user_id', $user->id);
    }

    // Filters
    $query->when($request->filled('user_id'), fn($q) =>
        $q->where('user_id', $request->user_id)
    )
    ->when($request->filled('txn_id'), fn($q) =>
        $q->where('txn_id', $request->txn_id)
    )
    ->when($request->filled('status'), fn($q) =>
        $q->where('status', strtoupper($request->status))
    )
    ->when($request->filled('route'), fn($q) =>
        $q->where('route', strtoupper($request->route))
    )
     ->when($request->filled('operator_name'), fn($q) =>
        $q->where('operator_name', strtoupper($request->operator_name))
    )
    ->when($request->filled('service_name'), fn($q) =>
        $q->where('service_name', strtoupper(str_replace('+', ' ', $request->service_name)))
    )
    ->when($request->filled('from_date') && $request->filled('to_date'), fn($q) =>
        $q->whereBetween('created_at', [
            $request->from_date . ' 00:00:00',
            $request->to_date . ' 23:59:59'
        ])
    );

    if ($request->input('export') == 1) {
       ini_set('memory_limit', '512M');
        $transactions = $query->get();
        
        return ResponseHelper::success('Transactions fetched successfully', $transactions, 200);
    }

    // Pagination
    $transactions = $query->paginate($request->input('paginate', 15));

    return ResponseHelper::success('Transactions fetched successfully', $transactions, 200);
}


    private function getMdDownlineIds($md){
        $diIds = User::where('parent_id', $md->id)->pluck('id')->toArray();
        $retIds = User::whereIn('parent_id', $diIds)->pluck('id')->toArray();
        return array_merge([$md->id], $diIds, $retIds);
    }

    private function getDiDownlineIds($di){
        $retIds = User::where('parent_id', $di->id)->pluck('id')->toArray();
        return array_merge([$di->id], $retIds);
    }

    public function userCommssionReport(Request $request){
        $user = Auth::user();
    
        // Base query
        if ($user->role === 'adm' || $user->role === 'sadm') {
            $query = DB::table('service_summaries')
                ->select(
                    'user_id',
                    'service_name',
                    DB::raw('SUM(total_amount) AS total_amount'),
                    DB::raw('SUM(total_comm) AS total_comm'),
                    DB::raw('SUM(total_tds) AS total_tds'),
                    DB::raw('SUM(total_charges) AS total_charges'),
                    DB::raw('SUM(profit) AS total_profit')
                );
        } else {
            $query = DB::table('service_summaries')
                ->select(
                    'user_id',
                    'service_name',
                    DB::raw('SUM(total_amount) AS total_amount'),
                    DB::raw('SUM(total_comm) AS total_comm'),
                    DB::raw('SUM(total_tds) AS total_tds'),
                    DB::raw('SUM(total_charges) AS total_charges'),
                    DB::raw('SUM(profit) AS total_profit')
                )
                ->where('user_id', $user->id);
        }
    
        // Filters
        if ($request->filled('service_name')) {
            $query->where('service_name', strtoupper($request->service_name));
        }
    
        if ($request->filled('from_date') && $request->filled('to_date')) {
            $query->whereBetween('summary_date', [
                $request->from_date,
                $request->to_date
            ]);
        }
    
        if ($request->filled('user_id') && in_array($user->role, ['adm', 'md', 'sadm'])) {
            $query->where('user_id', $request->user_id);
        }
    
        // Grouping
        $query->groupBy('user_id', 'service_name')
              ->orderBy('user_id')
              ->orderBy('service_name');
    
        // Fetch results
        $results = $query->get();
    
        // Add user names
        $userIds = $results->pluck('user_id')->unique();
        $users = User::whereIn('id', $userIds)->pluck('establishment', 'id');
    
        $results->transform(function ($item) use ($users) {
            $item->user_name = $users[$item->user_id] ?? 'Unknown';
            return $item;
        });
    
        // 🔥 GRAND TOTALS (THIS IS WHAT YOU WANT)
        $totals = [
            'total_amount'   => $results->sum('total_amount'),
            'total_comm'     => $results->sum('total_comm'),
            'total_tds'      => $results->sum('total_tds'),
            'total_charges'  => $results->sum('total_charges'),
            'total_profit'   => $results->sum('total_profit')
        ];
    
        // Return with totals included
        return ResponseHelper::success('Service summary fetched successfully', [
            'data'   => $results,
            'totals' => $totals
        ], 200);
    }
}
