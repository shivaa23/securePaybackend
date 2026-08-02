<?php
namespace App\Modules\Bbps\Services;

use App\Modules\WalletLedger\Services\WalletService;
use App\Helper\ResponseHelper;
use Carbon\Carbon;
use App\Modules\User\Models\User;
class BbpsResponseHandler
{
    public static function handle($txn, $response, $user, $service, $comm)
    {
        // No response at all
        if (!isset($response['statuscode'])) {
            $txn->update([
                'status'       => 'PENDING',
                'operator_id'  => 'No Response',
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
        
            return ResponseHelper::error('Something went wrong', 'No response from provider', 500);
        }
    
        switch ($response['statuscode']) {
            case 'TXN': 
                $txn->update([
                    'status'       => 'SUCCESS',
                    'operator_id'  => $response['data']['txnReferenceId'] ?? null,
                    'api_response' => json_encode($response),
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
        
                return ResponseHelper::success($response['message'], $response, 200);
    
            case 'ERR': 
            case 'IRA': 
            case 'SPD': 
            case 'SPE': 
            case 'DTX': 
                WalletService::refund($user, $txn->amount, $txn, $service);
                $txn->update([
                    'status'       => 'FAILED',
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
                
                    WalletService::creditCommission($user, $txn->ret_comm, $txn, $service);
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
        
                return ResponseHelper::error('Invalid Response, contact support', $response, 403);
        }
    }
     public static function handleOffline($txn, $response, $user, $service, $comm)
    {
        if (!isset($response['status'])) {
            $txn->update([
                'status'       => 'PENDING',
                'operator_id'  => 'No Response',
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
        
            return ResponseHelper::error('Something went wrong', 'No response from provider', 500);
        }
    
        switch ($response['status']) {
            case 'SUCCESS': 
                $txn->update([
                    'status'       => 'SUCCESS',
                    'operator_id'  => $response['operator_id'] ?? 'Submitted Successfully',
                    'api_response' => json_encode($response),
                ]);
             
                    WalletService::creditCommission($user, $txn->ret_comm, $txn, $service);
                    $distributor = $user->parent ? User::find($user->parent) : null;
                    if ($distributor && $txn->di_comm > 0) {
                        WalletService::creditCommission($distributor, $txn->di_comm, $txn, $service);
                    }
                    $masterDistributor = $distributor && $distributor->parent ? User::find($distributor->parent) : null;
                    if ($masterDistributor && $txn->md_comm > 0) {
                        WalletService::creditCommission($masterDistributor, $txn->md_comm, $txn, $service);
                    }
        
                return ResponseHelper::success($response['message']??$response['status']??"SUCCESS", $response, 200);
    
            case 'FAILED': 
                WalletService::refund($user, $txn->amount, $txn, $service);
                $txn->update([
                    'status'       => 'FAILED',
                    'operator_id'  => $response['operator_id'] ?? null,
                    'api_response' => json_encode($response),
                ]);
                return ResponseHelper::error($response['message']??$response['status']??"FAILED", $response, 403);
    
            case 'PENDING': 
                $txn->update([
                    'status'       => 'PENDING',
                    'operator_id'  => $response['operator_id'] ?? null,
                    'api_response' => json_encode($response),
                ]);
                
                    WalletService::creditCommission($user, $txn->ret_comm, $txn, $service);
                    $distributor = $user->parent ? User::find($user->parent) : null;
                    if ($distributor && $txn->di_comm > 0) {
                        WalletService::creditCommission($distributor, $txn->di_comm, $txn, $service);
                    }
                    $masterDistributor = $distributor && $distributor->parent ? User::find($distributor->parent) : null;
                    if ($masterDistributor && $txn->md_comm > 0) {
                        WalletService::creditCommission($masterDistributor, $txn->md_comm, $txn, $service);
                    }
        
                return ResponseHelper::success('Transaction processed successfully', $response, 200);
    
            default: 
                $txn->update([
                    'status'       => 'PENDING',
                    'operator_id'  => 'Invalid Response',
                    'api_response' => json_encode($response),
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
        
                return ResponseHelper::error('Invalid Response, contact support', $response, 403);
        }
    }

}