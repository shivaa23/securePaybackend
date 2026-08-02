<?php

namespace App\Modules\WalletLedger\Controllers;

use Laravel\Lumen\Routing\Controller as BaseController;
use App\Traits\ApiResponser;
use Illuminate\Http\Request;
use App\Modules\WalletLedger\Models\WalletLedger;
use App\Modules\User\Models\User;
use App\Modules\WalletLedger\Models\WalletLedgerValidation;
use Auth;
use App\Helper\ResponseHelper;
use Illuminate\Support\Facades\DB;

class WalletLedgerController extends BaseController
{
    use ApiResponser;

    public function __construct()
    {
        $this->middleware('auth', ['except' => ['']]);
        $this->middleware('check.active', ['except' => '']);

    }
    //   public function getWalletLedgers(Request $request)
    // {
    //     $authUser = Auth::user();
    //     $query = WalletLedger::with('user')->orderBy('id', 'desc');
    
    //     if (!in_array($authUser->role, ['sadm', 'adm'])) {
    //         if ($request->filled('user_id')) {
    //             $query->where('user_id', $request->user_id);
    //         }
    //         if ($request->filled('txn_id')) {
    //             $query->where('txn_id', $request->txn_id);
    //         }
    //         if ($request->filled('service_name')) {
    //             $query->where('service', $request->service_name);
    //         }
    //     }else {
    //         if ($request->filled('user_id')) {
    //             $query->where('user_id', $request->user_id);
    //         }
    //         if ($request->filled('txn_id')) {
    //             $query->where('txn_id', $request->txn_id);
    //         }
    //         if ($request->filled('service_name')) {
    //             $query->where('service', $request->service_name);
    //         }
    //         // if ($request->filled('mobile')) {
    //         //     $user = User::where('mobile', $request->mobile)->first();
    //         //     if (!$user) {
    //         //         return ResponseHelper::success('No Data Found', [], 200);
    //         //     }
    //         //     $query->where('user_id', $user->id);
    //         // }
    //     }
    //     if ($request->filled('from_date') && $request->filled('to_date')) {
    //         $query->whereDate('created_at', '>=', $request->from_date)
    //               ->whereDate('created_at', '<=', $request->to_date);
    //     }
    //     $walletledgers = $request->filled('export') && $request->export == 1
    //         ? $query->get()
    //         : $query->paginate((int) $request->input('paginate', 15));
    
    //     $collection = $walletledgers instanceof \Illuminate\Pagination\LengthAwarePaginator
    //         ? $walletledgers->getCollection()
    //         : $walletledgers;
    
    //     $collection->transform(function ($ledger) {
    //         $ledger->establishment = $ledger->user->establishment ?? 'Unknown';
    //         unset($ledger->user);
    //         return $ledger;
    //     });
    //     if ($walletledgers->isNotEmpty()) {
    //         return ResponseHelper::success('Wallet Ledgers fetched', $walletledgers, 200);
    //     } else {
    //         return ResponseHelper::success('No Data Found', [], 200);
    //     }
    // }
    public function getWalletLedgers(Request $request)
    {
        $user = Auth::user();
    
        $query = WalletLedger::with('user')->orderBy('id', 'desc');
    
        if (!in_array($user->role, ['sadm', 'adm'])) {
            $userIds = array_merge([$user->id], User::getDescendantUserIds($user->id) ?? []);
            $query->whereIn('user_id', $userIds);
        }
    
        if ($request->filled('user_id')) {
            $query->where('user_id', $request->user_id);
        }
    
        if ($request->filled('txn_id')) {
            $query->where('txn_id', $request->txn_id);
        }
    
        if ($request->filled('service_name')) {
            $query->where('service', $request->service_name);
        }
    
        if ($request->filled('from_date') && $request->filled('to_date')) {
                $query->whereDate('created_at', '>=', $request->from_date)
                      ->whereDate('created_at', '<=', $request->to_date);
            }
    
        $walletLedgers = $request->filled('export') && $request->export == 1
            ? $query->get()
            : $query->paginate((int)$request->input('paginate', 15));
    
        $collection = $walletLedgers instanceof \Illuminate\Pagination\LengthAwarePaginator
            ? $walletLedgers->getCollection()
            : $walletLedgers;
    
        $collection->transform(function ($row) {
            $row->establishment = $row->user->establishment ?? 'Unknown';
            unset($row->user);
            return $row;
        });
    
        if ($collection->isNotEmpty()) {
            return ResponseHelper::success('Wallet Ledgers fetched', $walletLedgers, 200);
        } else {
            return ResponseHelper::success('No Data Found', [], 200);
        }
    }



public function getWalletLedgersDi(Request $request)
{
    $user = Auth::user();

    // Allow only DI or MD
    if (!in_array($user->role, ['di', 'md'])) {
        return ResponseHelper::error('Unauthorized Access', [], 401);
    }

    $query = WalletLedger::with('user')
        ->where('user_id', $user->id)
        ->orderBy('id', 'desc');
    if ($request->filled('txn_id')) {
        $query->where('txn_id', $request->txn_id);
    }
    if ($request->filled('service_name')) {
        $query->where('service', $request->service_name);
    }
    if ($request->filled('from_date') && $request->filled('to_date')) {
        $query->whereDate('created_at', '>=', $request->from_date)
              ->whereDate('created_at', '<=', $request->to_date);
    }
    $walletLedgers = ($request->filled('export') && $request->export == 1)
        ? $query->get()
        : $query->paginate((int)$request->input('paginate', 15));
    $collection = $walletLedgers instanceof \Illuminate\Pagination\LengthAwarePaginator
        ? $walletLedgers->getCollection()
        : $walletLedgers;

    $collection->transform(function ($row) {
        $row->establishment = $row->user->establishment ?? 'Unknown';
        unset($row->user);
        return $row;
    });
    if ($collection->isNotEmpty()) {
        return ResponseHelper::success('Wallet Ledgers fetched', $walletLedgers, 200);
    }

    return ResponseHelper::success('No Data Found', [], 200);
}




public function getTxnData(Request $request)
{
    $txnId = $request->txn_id;

    if (!$txnId) {
        return ResponseHelper::error('txn_id is required', [], 400);
    }

    // Map txn_id prefixes to tables
    $tableMap = [
        'PMT'   => 'payout_txns',
        'DMT1'   => 'dmt_txns',
        'DMT2'  => 'dmt_txns',
        'ACV'  => 'verification_txns',
        'AEPS'  => 'aeps_txns',
        'W2W'  => 'wallet_transactions',
        'W3W'  => 'wallet_transactions',
        'AEPS2' => 'aeps_txns',
        'BBP'  => 'bbps_txns',
        'RCH'   => 'recharge_txns'
    ];

    $results = [];

    // Determine which table the txn_id belongs to based on its prefix
   foreach ($tableMap as $prefix => $table) {
    if (str_starts_with($txnId, $prefix)) { // or Str::startsWith($txnId, $prefix)
        $data = DB::table($table)
            ->where('txn_id', $txnId)
            ->first(); // txn_id should be unique

        if ($data) {
            $results = $data;
        }
        break; // stop after finding the matching table
        }
    }

    if (empty($results)) {
        return ResponseHelper::error('Transaction not found', [], 404);
    }

    return ResponseHelper::success('Transaction fetched', $results);
}


}
