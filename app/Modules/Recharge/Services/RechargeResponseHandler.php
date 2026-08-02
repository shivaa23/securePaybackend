<?php
namespace App\Modules\Recharge\Services;

use App\Modules\WalletLedger\Services\WalletService;
use App\Helper\ResponseHelper;
use App\Modules\User\Models\User;
class RechargeResponseHandler
{
    public static function handle($txn, $response, $user, $service, $comm)
    {
        if (!isset($response['status'])) {
            $txn->update([
                'api_response' => json_encode(['error' => 'No response']),
            ]);
             WalletService::creditCommission($user, $txn->ret_comm, $txn, $service);
    
                // Distributor commission
                $distributor = $user->parent ? User::find($user->parent) : null;
                if ($distributor && $txn->di_comm > 0) {
                    WalletService::creditCommission($distributor, $txn->di_comm, $txn, $service);
                }
    
                // Master Distributor commission
                $masterDistributor = $distributor && $distributor->parent ? User::find($distributor->parent) : null;
                if ($masterDistributor && $txn->md_comm > 0) {
                    WalletService::creditCommission($masterDistributor, $txn->md_comm, $txn, $service);
                }
            return ResponseHelper::error('Recharge Failed', 'No response', 500);
        }

        switch ($response['status']) {
            case 'SUCCESS':
                $txn->update([
                    'status'       => 'SUCCESS',
                    'operator_id'  => $response['operator_id'],
                    'api_response' => json_encode($response['response']),
                ]);
               WalletService::creditCommission($user, $txn->ret_comm, $txn, $service);
    
                // Distributor commission
                $distributor = $user->parent ? User::find($user->parent) : null;
                if ($distributor && $txn->di_comm > 0) {
                    WalletService::creditCommission($distributor, $txn->di_comm, $txn, $service);
                }
    
                // Master Distributor commission
                $masterDistributor = $distributor && $distributor->parent ? User::find($distributor->parent) : null;
                if ($masterDistributor && $txn->md_comm > 0) {
                    WalletService::creditCommission($masterDistributor, $txn->md_comm, $txn, $service);
                }
                return ResponseHelper::success('Recharge Success', $response, 200);

            case 'FAILED':
                WalletService::refund($user, $txn->amount, $txn, $service);
                $txn->update([
                    'status'       => 'FAILED',
                    'operator_id'  => $response['operator_id'],
                    'api_response' => json_encode($response['response']),
                ]);
                return ResponseHelper::error('Recharge Failed', $response, 403);

            case 'PENDING':
                $txn->update([
                    'operator_id'  => $response['operator_id'],
                    'api_response' => json_encode($response['response']),
                ]);
              WalletService::creditCommission($user, $txn->ret_comm, $txn, $service);
    
                // Distributor commission
                $distributor = $user->parent ? User::find($user->parent) : null;
                if ($distributor && $txn->di_comm > 0) {
                    WalletService::creditCommission($distributor, $txn->di_comm, $txn, $service);
                }
    
                // Master Distributor commission
                $masterDistributor = $distributor && $distributor->parent ? User::find($distributor->parent) : null;
                if ($masterDistributor && $txn->md_comm > 0) {
                    WalletService::creditCommission($masterDistributor, $txn->md_comm, $txn, $service);
                }
                return ResponseHelper::error('Recharge Pending', $response, 403);

            default:
                $txn->update([
                    'api_response' => json_encode($response['response']),
                ]);
                 WalletService::creditCommission($user, $txn->ret_comm, $txn, $service);
    
                // Distributor commission
                $distributor = $user->parent ? User::find($user->parent) : null;
                if ($distributor && $txn->di_comm > 0) {
                    WalletService::creditCommission($distributor, $txn->di_comm, $txn, $service);
                }
    
                // Master Distributor commission
                $masterDistributor = $distributor && $distributor->parent ? User::find($distributor->parent) : null;
                if ($masterDistributor && $txn->md_comm > 0) {
                    WalletService::creditCommission($masterDistributor, $txn->md_comm, $txn, $service);
                }
                //WalletService::creditCommission($user, $txn->comm, $txn, $service);
                return ResponseHelper::error('Recharge Unknown', $response, 403);
        }
    }
}