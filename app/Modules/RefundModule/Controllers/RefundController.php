<?php

namespace App\Modules\RefundModule\Controllers;

use Laravel\Lumen\Routing\Controller as BaseController;
use App\Traits\ApiResponser;
use Illuminate\Http\Request;
use Auth;
use Illuminate\Support\Facades\DB;
use App\Helper\ResponseHelper;
use Carbon\Carbon;
use App\Modules\WalletLedger\Models\WalletLedger;
use App\Modules\WalletLedger\Models\WalletLedger2;
use App\Modules\User\Models\User;
use App\Modules\Dashboard\Models\ServiceSummaries;
use App\Modules\RefundModule\Models\RefundValidations;
use App\Modules\Service\Models\Service;
use App\Modules\WalletLedger\Services\WalletService2;
use App\Modules\WalletLedger\Services\WalletService;

class RefundController extends BaseController
{
    use ApiResponser;

    public function __construct()
    {
        $this->middleware('auth', ['except' => ['createAccount', 'updateAccount', 'encryptPayload','refundTxn']]);
        $this->middleware('check.active', ['except' => '']);
    }

    public function refundTxn(Request $request)
    {
        if(!Auth::user()){
            if($request->ip() != '103.49.124.232'){
                return ResponseHelper::error('Unauthorised Action', '', 403);
            }            
        }
        $validator = RefundValidations::refund($request->all());
        if ($validator->fails()) {
            return ResponseHelper::error('Validation Failed', $validator->errors(), 422);
        }
    
        $txnId = $request->txn_id;
        $ledgerEntries = WalletLedger::where("txn_id", $txnId)->get();
        if ($ledgerEntries->isEmpty()) {
            return ResponseHelper::error('Ledger not found for this transaction', [], 404);
        }
    
        $tableMap = [
            'PMT'   => 'payout_txns',
            'DMT1'  => 'dmt_txns',
            'DMT2'  => 'dmt_txns',
            'ACV'  => 'verification_txns',
            'BBP'   => 'bbps_txns',
            'RCH'   => 'recharge_txns',
            'CARD'  => 'credit_cards'
        ];
    
        $txn = null;
        $tableName = null;
        foreach ($tableMap as $prefix => $table) {
            if (str_starts_with($txnId, $prefix)) {
                $txn = DB::table($table)->where('txn_id', $txnId)->first();
                $tableName = $table;
                break;
            }
        }
        $userId = $txn->user_id;
        if (!$txn) {
            return ResponseHelper::error('Transaction not found', [], 404);
        }
    
        if (!in_array($txn->status, ['PENDING', 'SUCCESS'])) {
            return ResponseHelper::error('Refund not allowed. Transaction status is ' . $txn->status, [], 403);
        }
    
        DB::beginTransaction();
        try {
            
            foreach ($ledgerEntries as $entry) {
                $user = User::find($entry->user_id);
                

                if (!$user) {
                    continue;
                }
                $opening = $user->w1 / 100;
                $amount = $entry->amount * 100;
                if ($entry->txn_type === 'DR') {
                    $user->incrementW1($amount);
                } elseif ($entry->txn_type === 'CR') {
                    $user->decrementW1($amount);
                }
                $refundLedger = new WalletLedger();
                $refundLedger->type = 'refund';
                $refundLedger->user_id = $user->id;
                $refundLedger->service = $entry->service;
                $refundLedger->txn_id = $txnId;
                 if ($entry->txn_type === 'DR') {
                 $refundLedger->txn_type = 'CR';
                } elseif ($entry->txn_type === 'CR') {
                 $refundLedger->txn_type = 'DR';
                }
                
                $refundLedger->amount = $amount / 100;
                $refundLedger->opening_balance = $opening;
                $refundLedger->closing_balance = $user->w1 / 100;
                
                if ($entry->txn_type === 'DR') {
                 $refundLedger->narration = 'Refund for ' . $entry->service;
                } elseif ($entry->txn_type === 'CR') {
                $refundLedger->narration = 'Commission reversal for ' . $entry->service;
                }
                
                $refundLedger->status = 'SUCCESS';
                $refundLedger->save();
            }
    
            DB::table($tableName)
                ->where('txn_id', $txnId)->update([
                    'status' => 'REFUNDED',
                   
                ]);
                $dateOnly = date('Y-m-d', strtotime($txn->created_at));
                $summury = ServiceSummaries::where("user_id", $userId)->where("summary_date",$dateOnly)->first();
            //   return $summury;
            if ($summury) {
                $summury->a_comm       = $summury->a_comm - ($txn->a_comm ?? 0);
                $summury->di_comm      = $summury->di_comm - ($txn->di_comm ?? 0);
                $summury->total_amount      = $summury->total_amount - ($txn->amount ?? 0);
                $summury->di_tds       = $summury->di_tds - ($txn->di_tds ?? 0);
                $summury->md_comm      = $summury->md_comm - ($txn->md_comm ?? 0);
                $summury->md_tds       = $summury->md_tds - ($txn->md_tds ?? 0);
                $summury->total_charges= $summury->total_charges - ($txn->charges ?? 0);
                $summury->total_comm   = $summury->total_comm -$txn->ret_comm;
                $summury->total_tds   = $summury->total_tds - $txn->ret_tds;                        
                $summury->save();
            }
    
            DB::commit();
            return ResponseHelper::success('Refund processed successfully', [
                'txn_id' => $txnId,
                'refunded_users' => $ledgerEntries->pluck('user_id')
            ]);
        } catch (\Exception $e) {
            DB::rollBack();
            return ResponseHelper::error('Refund Failed', $e->getMessage(), 500);
        }
    }
    public function successAepsTxn(Request $request){
    
        if (!in_array(Auth::user()->role, ['adm', 'sadm'])) {
        return ResponseHelper::error('Unauthorized', [], 401);
        }
    
        $validator = RefundValidations::refund($request->all());
        if ($validator->fails()) {
            return ResponseHelper::error('Validation Failed', $validator->errors(), 422);
        }
       
        $txnId = $request->txn_id;
        $ledgerEntries = WalletLedger2::where("txn_id", $txnId)->get();

        if ($ledgerEntries->isNotEmpty()) {
            return ResponseHelper::error('Transaction already processed', [], 400);
        }

        $tableMap = [
            'AEPS' => 'aeps_txns',
        ];
    
        $tableName = null;
    
        foreach ($tableMap as $prefix => $table) {
            if (strpos($txnId, $prefix) === 0) {
                $tableName = $table;
                break;
            }
        }
    
        if (!$tableName) {
            return ResponseHelper::error('Invalid Transaction Prefix', [], 400);
        }
    
        DB::beginTransaction();
    
        try {
    
            $txn = DB::table($tableName)
                ->where('txn_id', $txnId)
                ->lockForUpdate()
                ->first();
            if (!$txn) {
                DB::rollBack();
                return ResponseHelper::error('Transaction not found', [], 404);
            }
          
            if ($txn->status != 'PENDING') {
                DB::rollBack();
                return ResponseHelper::error(
                    'Refund not allowed. Current status: ' . $txn->status,
                    [],
                    403
                );
            }
             if (!in_array($txn->operator, ['Aeps 1 Cash Withdrawal', 'Aeps 2 Cash Withdrawal'])) {
                DB::rollBack();
                return ResponseHelper::error('Invalid Operator/Service', [], 400);
            }
            $service = Service::where('name', $txn->operator)->first();
            if (!$service) {
                DB::rollBack();
                return ResponseHelper::error('Invalid Operator/Service', [], 400);
            }
    
            $user = User::find($txn->user_id);
            if (!$user) {
                DB::rollBack();
                return ResponseHelper::error('User not found', [], 404);
            }
            WalletService2::credit($user,$txn->amount, $txn, $service);
            WalletService2::creditCommission($user, $txn, $service);
            if (!empty($user->parent)) {
                $distributor = User::find($user->parent);
    
                if ($distributor && $txn->di_comm > 0) {
                    WalletService2::creditCommission($distributor, $txn, $service);
                }
    
                if ($distributor && !empty($distributor->parent)) {
                    $masterDistributor = User::find($distributor->parent);
    
                    if ($masterDistributor && $txn->md_comm > 0) {
                        WalletService2::creditCommission($masterDistributor, $txn, $service);
                    }
                }
            }
            DB::table($tableName)
                ->where('txn_id', $txnId)
                ->update([
                    'status' => 'SUCCESS',
                ]);
    
            DB::commit();
    
            return ResponseHelper::success('Refund processed successfully', [
                'txn_id' => $txnId,
            ]);
    
        } catch (\Throwable $e) {
            DB::rollBack();
            return ResponseHelper::error('Refund Failed', $e->getMessage(), 500);
        }
    }
    // public function successTxn(Request $request)
    // {
    //     $user=Auth::user();
    //       $validator = RefundValidations::successTxn($request->all());
    //     if ($validator->fails()) {
    //         return ResponseHelper::error('Validation Failed', $validator->errors(), 422);
    //     }
    //   if (!in_array($user->role, ['adm', 'sadm'])) {
    //     return ResponseHelper::error('Unauthorized', [], 401);
    // }

    //     $txnId = $request->txn_id;
    
    //     $tableMap = [
    //         'PMT'   => 'payout_txns',
    //         'DMT1'  => 'dmt_txns',
    //         'DMT2'  => 'dmt_txns',
    //         'ACV'   => 'dmt_txns',
    //         'BBP'   => 'bbps_txns',
    //         'RCH'   => 'recharge_txns',
    //         'CARD'  => 'credit_cards'
    //     ];
    
    //     $txn = null;
    //     $tableName = null;
    
    //     foreach ($tableMap as $prefix => $table) {
    //         if (str_starts_with($txnId, $prefix)) {
    //             $txn = DB::table($table)
    //             ->where('txn_id', $txnId)
    //             ->whereIn('status', ['REFUNDPENDING', 'PENDING'])
    //             ->update([
    //                 'operator_id' => $request->operator_id,
    //                 'status'      => 'SUCCESS',
    //             ]);
    //             if(!$txn){
    //                 return ResponseHelper::error('Transaction Already Altered or not found', '', 403);
    //             }
    //             return ResponseHelper::success('Transaction Success', '', 201);
    //         }
    //     }
    //     return ResponseHelper::error('Transaction Not Found', '', 201);
    // }
    
    // public function failedTxn(Request $request)
    // {
    //     $user=Auth::user();
    //     $validator = RefundValidations::successTxn($request->all());
    //     if ($validator->fails()) {
    //         return ResponseHelper::error('Validation Failed', $validator->errors(), 422);
    //     }
    //       if (!in_array($user->role, ['adm', 'sadm'])) {
    //     return ResponseHelper::error('Unauthorized', [], 401);
    // }
    //     $txnId = $request->txn_id;
    
    //     $tableMap = [
    //         'PMT'   => 'payout_txns',
    //         'DMT1'  => 'dmt_txns',
    //         'DMT2'  => 'dmt_txns',
    //         'ACV'   => 'dmt_txns',
    //         'BBP'   => 'bbps_txns',
    //         'RCH'   => 'recharge_txns',
    //         'CARD'  => 'credit_cards'
    //     ];
    
    //     $txn = null;
    //     $tableName = null;
    //       foreach ($tableMap as $prefix => $table) {
    //         if (str_starts_with($txnId, $prefix)) {
    //             $txn = DB::table($table)
    //             ->where('txn_id', $txnId)
    //             ->whereIn('status', ['PENDING'])
    //             ->update([
    //                 'operator_id' => $request->operator_id,
    //                 'status'      => 'FAILED',
    //             ]);
    //             if(!$txn){
    //                 return ResponseHelper::error('Transaction Already Failed or Success or not found', '', 403);
    //             }
    //             return ResponseHelper::success('Transaction failed', '', 201);
    //         }
    //     }
    //     return ResponseHelper::error('Transaction Not Found', '', 201);
    // }
    public function successTxn(Request $request)
{
    $user = Auth::user();

    // Validate input
    $validator = RefundValidations::successTxn($request->all());
    if ($validator->fails()) {
        return ResponseHelper::error('Validation Failed', $validator->errors(), 422);
    }

    // Role check
    if (!in_array($user->role, ['adm', 'sadm'])) {
        return ResponseHelper::error('Unauthorized', [], 401);
    }

    $txnId = $request->txn_id;

    $tableMap = [
        'PMT'   => 'payout_txns',
        'DMT1'  => 'dmt_txns',
        'DMT2'  => 'dmt_txns',
        'AEPS'  => 'aeps_txns',
        'ACV'   => 'verification_txns',
        'BBP'   => 'bbps_txns',
        'RCH'   => 'recharge_txns',
        'CARD'  => 'credit_cards'
    ];

    foreach ($tableMap as $prefix => $table) {
        if (str_starts_with($txnId, $prefix)) {

            // AEPS special case: update RRN
            $updateData = ($table === 'aeps_txns')
                ? ['rrn' => $request->operator_id, 'status' => 'SUCCESS']
                : ['operator_id' => $request->operator_id, 'status' => 'SUCCESS'];

            $txn = DB::table($table)
                ->where('txn_id', $txnId)
                ->whereIn('status', ['REFUNDPENDING', 'PENDING'])
                ->update($updateData);

            if (!$txn) {
                return ResponseHelper::error('Transaction already altered or not found', '', 403);
            }

            return ResponseHelper::success('Transaction marked as SUCCESS', '', 201);
        }
    }

    return ResponseHelper::error('Transaction Not Found', '', 404);
}
// public function successTxnnewaeps(Request $request)
// {
//     $user = Auth::user();

//     // Validate input
//     $validator = RefundValidations::successTxn($request->all());
//     if ($validator->fails()) {
//         return ResponseHelper::error('Validation Failed', $validator->errors(), 422);
//     }

//     // Role check
//     if (!in_array($user->role, ['adm', 'sadm'])) {
//         return ResponseHelper::error('Unauthorized', [], 401);
//     }

//     $txnId = $request->txn_id;

//     $tableMap = [
//         'PMT'   => 'payout_txns',
//         'DMT1'  => 'dmt_txns',
//         'DMT2'  => 'dmt_txns',
//         'AEPS'  => 'aeps_txns',
//         'ACV'   => 'verification_txns',
//         'BBP'   => 'bbps_txns',
//         'RCH'   => 'recharge_txns',
//         'CARD'  => 'credit_cards'
//     ];

//     foreach ($tableMap as $prefix => $table) {
//         if (str_starts_with($txnId, $prefix)) {

//             // Fetch transaction before updating
//             $txn = DB::table($table)
//                 ->where('txn_id', $txnId)
//                 ->whereIn('status', ['REFUNDPENDING', 'PENDING'])
//                 ->first();

//             if (!$txn) {
//                 return ResponseHelper::error('Transaction already altered or not found', '', 403);
//             }

//             // Prepare update data
//             $updateData = ($table === 'aeps_txns')
//                 ? ['rrn' => $request->operator_id, 'status' => 'SUCCESS']
//                 : ['operator_id' => $request->operator_id, 'status' => 'SUCCESS'];

//             // Update the transaction
//             DB::table($table)
//                 ->where('txn_id', $txnId)
//                 ->update($updateData);

//             // AEPS special: credit wallet and commissions
//             if ($table === 'aeps_txns') {
//                 $service = Service::where("name", $txn->operator)->first();
//                 WalletService2::credit($user, $txn->amount, $txn, $service);
//                 WalletService2::creditCommission($user, $txn, $service);

//                 $distributor = $user->parent ? User::find($user->parent) : null;
//                 if ($distributor && $txn->di_comm > 0) {
//                     WalletService2::creditCommission($distributor, $txn, $service);
//                 }

//                 $masterDistributor = $distributor && $distributor->parent ? User::find($distributor->parent) : null;
//                 if ($masterDistributor && $txn->md_comm > 0) {
//                     WalletService2::creditCommission($masterDistributor, $txn, $service);
//                 }
//             }

//             return ResponseHelper::success('Transaction marked as SUCCESS', '', 201);
//         }
//     }

//     return ResponseHelper::error('Transaction Not Found', '', 404);
// }


public function failedTxn(Request $request)
{
    $user = Auth::user();

    // Validate input
    $validator = RefundValidations::successTxn($request->all());
    if ($validator->fails()) {
        return ResponseHelper::error('Validation Failed', $validator->errors(), 422);
    }

    // Role check
    if (!in_array($user->role, ['adm', 'sadm'])) {
        return ResponseHelper::error('Unauthorized', [], 401);
    }

    $txnId = $request->txn_id;

    $tableMap = [
        'PMT'   => 'payout_txns',
        'DMT1'  => 'dmt_txns',
        'DMT2'  => 'dmt_txns',
        'AEPS'  => 'aeps_txns',
        'ACV'   => 'verification_txns',
        'BBP'   => 'bbps_txns',
        'RCH'   => 'recharge_txns',
        'CARD'  => 'credit_cards'
    ];

    foreach ($tableMap as $prefix => $table) {
        if (str_starts_with($txnId, $prefix)) {

            // AEPS special case: update RRN
            $updateData = ($table === 'aeps_txns')
                ? ['rrn' => $request->operator_id, 'status' => 'FAILED']
                : ['operator_id' => $request->operator_id, 'status' => 'FAILED'];

            $txn = DB::table($table)
                ->where('txn_id', $txnId)
                ->whereIn('status', ['PENDING'])
                ->update($updateData);

            if (!$txn) {
                return ResponseHelper::error('Transaction already altered or not found', '', 403);
            }

            return ResponseHelper::success('Transaction marked as FAILED', '', 201);
        }
    }

    return ResponseHelper::error('Transaction Not Found', '', 404);
}



}
