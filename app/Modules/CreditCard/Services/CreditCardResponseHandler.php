<?php
namespace App\Modules\CreditCard\Services;

use App\Modules\WalletLedger\Services\WalletService;
use App\Helper\ResponseHelper;
use Carbon\Carbon;
use App\Modules\User\Models\User;
class CreditCardResponseHandler
{
 public static function handle($txn, $response, $user, $service, $comm)
{
    // No response at all
    if (!isset($response['statuscode'])) {
        $txn->update([
            'operator_id'  => 'No Response',
            'api_response' => json_encode(['error' => 'No response']),
        ]);
         if ($txn->ret_comm > 0) {
                WalletService::creditCommission($user, $txn->ret_comm, $txn, $service);
            }
            $distributor = $user->parent ? User::find($user->parent) : null;
            if ($distributor && $txn->di_comm > 0) {
                WalletService::creditCommission($distributor, $txn->di_comm, $txn, $service);
            }
            $masterDistributor = $distributor && $distributor->parent ? User::find($distributor->parent) : null;
            if ($masterDistributor && $txn->md_comm > 0) {
                WalletService::creditCommission($masterDistributor, $txn->md_comm, $txn, $service);
            };
        return ResponseHelper::error('Something went wrong', 'No response from provider', 500);
    }

    switch ($response['statuscode']) {
        case 'TXN': 
            $txn->update([
                'status'       => 'SUCCESS',
                'operator_id'  => $response['data']['txnReferenceId'] ?? null,
                'api_response' => json_encode($response),
            ]);
          
            if ($txn->ret_comm > 0) {
                WalletService::creditCommission($user, $txn->ret_comm, $txn, $service);
            }
            $distributor = $user->parent ? User::find($user->parent) : null;
            if ($distributor && $txn->di_comm > 0) {
                WalletService::creditCommission($distributor, $txn->di_comm, $txn, $service);
            }
            $masterDistributor = $distributor && $distributor->parent ? User::find($distributor->parent) : null;
            if ($masterDistributor && $txn->md_comm > 0) {
                WalletService::creditCommission($masterDistributor, $txn->md_comm, $txn, $service);
            };
            return ResponseHelper::success('Transaction Successful', $response, 200);

        case 'ERR': 
        case 'IRA': 
        case 'SPD': 
        case 'SPE': 
        case 'DTX': 
        case 'VAL': 
            WalletService::refund($user, $txn->amount, $txn, $service);
            $txn->update([
                'status'       => $response['status'],
                'operator_id'  => $response['status'] ?? null,
                'api_response' => json_encode($response),
            ]);
            return ResponseHelper::error($response['message'], $response, 403);

        case 'TUP': 
            $txn->update([
                'status'       => 'PENDING',
                'operator_id'  => $response['status'] ?? null,
                'api_response' => json_encode($response),
            ]);
            
            if ($txn->ret_comm > 0) {
                WalletService::creditCommission($user, $txn->ret_comm, $txn, $service);
            }
            $distributor = $user->parent ? User::find($user->parent) : null;
            if ($distributor && $txn->di_comm > 0) {
                WalletService::creditCommission($distributor, $txn->di_comm, $txn, $service);
            }
            $masterDistributor = $distributor && $distributor->parent ? User::find($distributor->parent) : null;
            if ($masterDistributor && $txn->md_comm > 0) {
                WalletService::creditCommission($masterDistributor, $txn->md_comm, $txn, $service);
            }
            return ResponseHelper::error('Transaction Pending', $response, 200);

        default: 
            $txn->update([
                'status'       => 'PENDING',
                'operator_id'  => 'Invalid Response',
                'api_response' => json_encode($response),
            ]);
            
            if ($txn->ret_comm > 0) {
                WalletService::creditCommission($user, $txn->ret_comm, $txn, $service);
            }
            $distributor = $user->parent ? User::find($user->parent) : null;
            if ($distributor && $txn->di_comm > 0) {
                WalletService::creditCommission($distributor, $txn->di_comm, $txn, $service);
            }
            $masterDistributor = $distributor && $distributor->parent ? User::find($distributor->parent) : null;
            if ($masterDistributor && $txn->md_comm > 0) {
                WalletService::creditCommission($masterDistributor, $txn->md_comm, $txn, $service);
            }
            return ResponseHelper::error('Invalid Response, contact support', $response, 403);
    }
}

}