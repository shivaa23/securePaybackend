<?php
namespace App\Modules\Payout\Services;

use App\Modules\WalletLedger\Services\WalletService;
use App\Helper\ResponseHelper;
use Carbon\Carbon;
use App\Modules\Payout\Models\PayoutBeneficiary;
use App\Modules\User\Models\User;
class PayoutResponseHandler
{
    public static function handle($txn, $response, $user, $service, $comm, $sender)
    {
        if (!isset($response['status'])) {
            $txn->update([
                'api_response' => json_encode(['error' => 'No response'])
            ]);
            $distributor = $user->parent ? User::find($user->parent) : null;
                if ($distributor && $txn->di_comm > 0) {
                    WalletService::creditCommission($distributor, $txn->di_comm, $txn, $service);
                }
            
                // 3. Master Distributor commission (parent of distributor)
                $masterDistributor = $distributor && $distributor->parent ? User::find($distributor->parent) : null;
                if ($masterDistributor && $txn->md_comm > 0) {
                    WalletService::creditCommission($masterDistributor, $txn->md_comm, $txn, $service);
                }
            return ResponseHelper::success('Fund Transfer Pending', 'No response', 201);
        }

        switch ($response['status']) {
            case 'SUCCESS':
                $txn->update([
                    'status'       => 'SUCCESS',
                    'operator_id'  => $response['operator_id'],
                    'api_response' => json_encode($response),
                ]);
                //WalletService::creditCommission($user, $txn->comm, $txn, $service);
                // 2. Distributor commission (parent of retailer)
                $distributor = $user->parent ? User::find($user->parent) : null;
                if ($distributor && $txn->di_comm > 0) {
                    WalletService::creditCommission($distributor, $txn->di_comm, $txn, $service);
                }
            
                // 3. Master Distributor commission (parent of distributor)
                $masterDistributor = $distributor && $distributor->parent ? User::find($distributor->parent) : null;
                if ($masterDistributor && $txn->md_comm > 0) {
                    WalletService::creditCommission($masterDistributor, $txn->md_comm, $txn, $service);
                }
                
                $sender->decrement('rem_limit',$txn->amount);
                return ResponseHelper::success('Fund Transfer Success', $response, 200);
            case 'PENDING':
                $txn->update([
                    'operator_id'  => $response['operator_id'],
                    'api_response' => json_encode($response),
                ]);
                //WalletService::creditCommission($user, $txn->comm, $txn, $service);
                // 2. Distributor commission (parent of retailer)
                $distributor = $user->parent ? User::find($user->parent) : null;
                if ($distributor && $txn->di_comm > 0) {
                    WalletService::creditCommission($distributor, $txn->di_comm, $txn, $service);
                }
            
                // 3. Master Distributor commission (parent of distributor)
                $masterDistributor = $distributor && $distributor->parent ? User::find($distributor->parent) : null;
                if ($masterDistributor && $txn->md_comm > 0) {
                    WalletService::creditCommission($masterDistributor, $txn->md_comm, $txn, $service);
                }
                
                $sender->decrement('rem_limit',$txn->amount);
                return ResponseHelper::success('Fund Transfer Success', $response, 200);

            case 'FAILED':
                WalletService::refund($user, $txn->amount, $txn, $service);
                $txn->update([
                    'status'       => 'FAILED',
                    'operator_id'  => $response['operator_id'],
                    'api_response' => json_encode($response),
                ]);
                return ResponseHelper::error($response['operator_id'], $response, 403);
            default:
                $txn->update([
                    'api_response' => json_encode($response),
                ]);
                //WalletService::creditCommission($user, $txn->comm, $txn, $service);
                $distributor = $user->parent ? User::find($user->parent) : null;
                if ($distributor && $txn->di_comm > 0) {
                    WalletService::creditCommission($distributor, $txn->di_comm, $txn, $service);
                }
            
                // 3. Master Distributor commission (parent of distributor)
                $masterDistributor = $distributor && $distributor->parent ? User::find($distributor->parent) : null;
                if ($masterDistributor && $txn->md_comm > 0) {
                    WalletService::creditCommission($masterDistributor, $txn->md_comm, $txn, $service);
                }
                $sender->decrement('rem_limit',$txn->amount);
                return ResponseHelper::error('Fund Transfer Unknown', $response, 403);
        }
    }
    public static function handleVerification($txn, $response, $user,$service,$comm, $route, $ben)
    {
        if (!isset($response['status'])) {
            $txn->update([
                'api_response' => json_encode(['error' => 'No response from API']),
            ]);
            return ResponseHelper::error('Fund Transfer Pending', 'No response', 500);
        }
        switch ($response['status']) {
            case 'SUCCESS':
                $txn->update([
                    'status'       => 'SUCCESS',
                    'operator_id'        => $response['data']['txnReferenceId'] ?? null,
                    'beneficiary_name'     => $response['name'],
                    'api_response' => json_encode($response),
                ]);
                $ben->update([
                        'beneficiary_name' => $response['name'],
                        'is_active'           => 1,
                        'is_verified'      => 1,
                    ]);
    
                return ResponseHelper::success('SUCCESS',$response['name'] ?? 'Verified',200);
    
            case 'FAILED':
                $txn->update([
                    'status'       => 'FAILED',
                    'operator_id'        => $response['status'] ?? 'Failed',
                    'api_response' => json_encode($response),
                ]);
                WalletService::refund($user, $txn->amount, $txn, $service);
    
                return ResponseHelper::error('FAILED',$response['status'] ?? 'Verification Failed',403);
    
            default:
                $txn->update([
                    'status'       => 'PENDING',
                    'operator_id'        => $response['status'] ?? null,
                    'api_response' => json_encode($response),
                ]);
    
                return ResponseHelper::success('Transaction Under Process',$response,202);
        }
    }
    
    public static function handleUpiVerification($txn, $response, $user,$service,$comm)
    {
        if (!isset($response['Status'])) {
            $txn->update([
                'api_response' => json_encode(['error' => 'No response from API']),
            ]);
            return ResponseHelper::error('Fund Transfer Pending', 'No response', 500);
        }
        switch ($response['Status']) {
            case 'Success':
                $txn->update([
                    'status'       => 'SUCCESS',
                    'operator_id'        => $response['data']['txnReferenceId'] ?? null,
                    'beneficiary_name'     => $response['Name'],
                    'api_response' => json_encode($response),
                ]);
                // $ben->update([
                //         'beneficiary_name' => $response['Name'],
                //         'is_active'           => 1,
                //         'is_verified'      => 1,
                //     ]);
    
                return ResponseHelper::success('SUCCESS',$response['Name'] ?? 'Verified',200);
    
            case 'Failed':
                $txn->update([
                    'status'       => 'FAILED',
                    'operator_id'        => $response['status'] ?? 'Failed',
                    'api_response' => json_encode($response),
                ]);
                WalletService::refund($user, $txn->amount, $txn, $service);
    
                return ResponseHelper::error('FAILED',$response['status'] ?? 'Verification Failed',403);
    
            default:
                $txn->update([
                    'status'       => 'PENDING',
                    'operator_id'        => $response['Status'] ?? null,
                    'api_response' => json_encode($response),
                ]);
    
                return ResponseHelper::success('Transaction Under Process',$response,202);
        }
    }
    public static function handleResponse($txn, $response, $user, $service, $comm, $sender)
    {
        if (!is_array($response) || !isset($response['responseCode'])) {
             if ($user->parent && $txn->di_comm > 0) {
                    $distributor = User::find($user->parent);
                    WalletService::creditCommission($distributor, $txn->di_comm, $txn, $service);
    
                    if ($distributor->parent && $txn->md_comm > 0) {
                        $masterDistributor = User::find($distributor->parent);
                        WalletService::creditCommission($masterDistributor, $txn->md_comm, $txn, $service);
                    }
                }
            return ResponseHelper::success('Transaction pending', [], 201);
        }
        $responseData = json_decode($response['responseData'], true);
        $transactionNumber = $responseData['Transfers'][0]['TransactionNumber'] ?? null;
        switch ($response['responseCode']) {
            case '200':
                $txn->update([
                    'status'        => 'SUCCESS',
                    'operator_id'   => "Transaction submitted successfully",
                    'api_id'=> $transactionNumber,
                    'api_response'  => json_encode($response),
                ]);
    
                // Commission credit logic
                if ($user->parent && $txn->di_comm > 0) {
                    $distributor = User::find($user->parent);
                    WalletService::creditCommission($distributor, $txn->di_comm, $txn, $service);
    
                    if ($distributor->parent && $txn->md_comm > 0) {
                        $masterDistributor = User::find($distributor->parent);
                        WalletService::creditCommission($masterDistributor, $txn->md_comm, $txn, $service);
                    }
                }
    
                $sender->decrement('rem_limit', $txn->amount);
    
                return ResponseHelper::success('Fund Transfer Success', $txn, 200);
            case '201':
                $txn->update([
                    'status'        => 'PENDING',
                    'operator_id'   => "Transaction Under Process",
                    'api_id'=> $transactionNumber,
                    'api_response'  => json_encode($response),
                ]);
    
                // Commission credit logic
                if ($user->parent && $txn->di_comm > 0) {
                    $distributor = User::find($user->parent);
                    WalletService::creditCommission($distributor, $txn->di_comm, $txn, $service);
    
                    if ($distributor->parent && $txn->md_comm > 0) {
                        $masterDistributor = User::find($distributor->parent);
                        WalletService::creditCommission($masterDistributor, $txn->md_comm, $txn, $service);
                    }
                }
    
                $sender->decrement('rem_limit', $txn->amount);
    
                return ResponseHelper::success('Fund Transfer Under Process', $txn, 200);
    
            case '400':
                WalletService::refund($user, $txn->amount, $txn, $service);
    
                $txn->update([
                    'status'       => 'FAILED',
                    'operator_id'  => "Transaction failed",
                    'api_response' => json_encode($response),
                ]);
    
                return ResponseHelper::error("Transaction failed", $response, 400);
    
            default:
                $txn->update([
                    'operator_id'  => "Transaction submitted successfully",
                    'api_response' => json_encode($response),
                ]);
    
                if ($user->parent && $txn->di_comm > 0) {
                    $distributor = User::find($user->parent);
                    WalletService::creditCommission($distributor, $txn->di_comm, $txn, $service);
    
                    if ($distributor->parent && $txn->md_comm > 0) {
                        $masterDistributor = User::find($distributor->parent);
                        WalletService::creditCommission($masterDistributor, $txn->md_comm, $txn, $service);
                    }
                }
    
                $sender->decrement('rem_limit', $txn->amount);
    
                return ResponseHelper::error('Fund Transfer Under Process', $response, 403);
        }
    }
    public static function handleResponseAbhyam($txn, $response, $user, $service, $comm, $sender)
    {
        // If response is invalid
        if (!is_array($response) || !isset($response['Status'])) {
    
            // Distributor commission
              if ($user->parent && $txn->di_comm > 0) {
                    $distributor = User::find($user->parent);
                    WalletService::creditCommission($distributor, $txn->di_comm, $txn, $service);
    
                    if ($distributor->parent && $txn->md_comm > 0) {
                        $masterDistributor = User::find($distributor->parent);
                        WalletService::creditCommission($masterDistributor, $txn->md_comm, $txn, $service);
                    }
                }
            return ResponseHelper::success('Transaction under process', [], 201);
        }   
    
        switch ($response['Status']) {
    
            case 'Success':
                 if ($user->parent && $txn->di_comm > 0) {
                                $distributor = User::find($user->parent);
                                WalletService::creditCommission($distributor, $txn->di_comm, $txn, $service);
                
                                if ($distributor->parent && $txn->md_comm > 0) {
                                    $masterDistributor = User::find($distributor->parent);
                                    WalletService::creditCommission($masterDistributor, $txn->md_comm, $txn, $service);
                                }
                            }
                $txn->update([
                    'status'        => 'SUCCESS',
                    'operator_id'   => $response['OpRefId'] ?? null,
                    'api_response'  => json_encode($response),
                ]);
    
                // Distributor commission
                 
                $sender->decrement('rem_limit', $txn->amount);
    
                return ResponseHelper::success('Fund Transfer Success', $txn, 200);
    
    
            case 'Pending':
    
                $txn->update([
                    'status'        => 'PENDING',
                    'operator_id'   => "Transaction Under Process",
                    'api_response'  => json_encode($response),
                ]);
    
                // Distributor commission
                  if ($user->parent && $txn->di_comm > 0) {
                    $distributor = User::find($user->parent);
                    WalletService::creditCommission($distributor, $txn->di_comm, $txn, $service);
    
                    if ($distributor->parent && $txn->md_comm > 0) {
                        $masterDistributor = User::find($distributor->parent);
                        WalletService::creditCommission($masterDistributor, $txn->md_comm, $txn, $service);
                    }
                }
    
                $sender->decrement('rem_limit', $txn->amount);
    
                return ResponseHelper::success('Fund Transfer Under Process', $txn, 200);
             case 'Failed':

            WalletService::refund($user, $txn->amount, $txn, $service);

            $txn->update([
                'status'        => 'FAILED',
                'operator_id'   => "Transaction failed",
                'api_response'  => json_encode($response),
            ]);

            return ResponseHelper::error("Transaction failed", $response, 400);
            default:
    
                $txn->update([
                    'operator_id'   => "Transaction submitted successfully",
                    'api_response'  => json_encode($response),
                ]);
    
                // Distributor commission
                 if ($user->parent && $txn->di_comm > 0) {
                    $distributor = User::find($user->parent);
                    WalletService::creditCommission($distributor, $txn->di_comm, $txn, $service);
    
                    if ($distributor->parent && $txn->md_comm > 0) {
                        $masterDistributor = User::find($distributor->parent);
                        WalletService::creditCommission($masterDistributor, $txn->md_comm, $txn, $service);
                    }
                }
    
                $sender->decrement('rem_limit', $txn->amount);
    
                return ResponseHelper::error('Fund Transfer Under Process', $response, 403);
        }
    }

    
}