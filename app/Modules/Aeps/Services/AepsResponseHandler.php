<?php
namespace App\Modules\Aeps\Services;

use App\Modules\WalletLedger\Services\WalletService2;
use App\Modules\WalletLedger\Services\WalletService;
use App\Modules\User\Models\User;
use App\Helper\ResponseHelper;
use Carbon\Carbon;

class AepsResponseHandler
{
    public static function handle($txn, $response, $user, $service, $comm)
    {
        if (!isset($response['status'])) {
            $txn->update([
                'api_response' => json_encode(['error' => 'No response'])
            ]);
            return ResponseHelper::error('Fund Transfer Pending', 'No response', 500);
        }

        switch ($response['status']) {
            case 'SUCCESS':
                $txn->update([
                    'status'       => 'SUCCESS',
                    'rrn'  => $response['operator_id'],
                    'api_response' => json_encode($response['response']),
                ]);
                 WalletService::creditCommission($user, $txn->comm, $txn, $service);
    
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
                return ResponseHelper::success('Fund Transfer Success', $response, 200);

            case 'FAILED':
              
                $txn->update([
                    'status'       => 'FAILED',
                    'rrn'  => $response['operator_id'],
                    'api_response' => json_encode($response['response']),
                ]);
                return ResponseHelper::error('Fund Transfer Failed', $response, 403);

            case 'PENDING':
                $txn->update([
                    'rrn'  => $response['operator_id'],
                    'api_response' => json_encode($response['response']),
                ]);
                return ResponseHelper::error('Fund Transfer Pending', $response, 403);

            default:
                $txn->update([
                    'api_response' => json_encode($response['response']),
                ]);
                return ResponseHelper::error('Fund Transfer Unknown', $response, 403);
        }
    }
    // public static function handleOutlet($txn, $response, $user, $service, $comm)
    // {
    //     if ($response['statuscode'] == 'TXN' && $response['data'] == 'LOGGEDIN') {
    //         $txn->update([
    //             'status'       => 'SUCCESS',
    //             'rrn'  => $response['status'] ?? 'LOGGEDIN',
    //             'api_response' => json_encode($response),
    //         ]);
    //         return ResponseHelper::success(
    //             $response['message'],
    //             $response,
    //             200
    //         );
    //     }else {
    //         $txn->update([
    //             'status'       => 'FAILED',
    //             'rrn'  => $response['status'] ?? 'LOGGEDIN REQ',
    //             'api_response' => json_encode($response),
    //         ]);
    
    //         WalletService::refund($user, $txn->amount, $txn, $service);
    //         return ResponseHelper::error(
    //             $response['message'] ?? 'Transaction failed',
    //             $response,
    //             403
    //         );
    //     }
    // }
     public static function handleOutlet($txn, $response, $user, $service, $comm)
    {
        if ($response['statuscode'] == 'TXN' && $response['message'] == 'LOGGEDIN') {
    
            $txn->update([
                'status'       => 'SUCCESS',
                'rrn'  => $response['status'] ?? 'LOGGEDIN',
                'api_response' => json_encode($response),
            ]);
    
            // WalletService::creditCommission($user, $txn->comm, $txn, $service);
    
            return ResponseHelper::success(
                $response['message'],
                $response,
                200
            );
    
        }  else {
    
            $txn->update([
                'status'       => 'FAILED',
                'rrn'  => $response['status'] ?? 'LOGGEDIN REQ',
                'api_response' => json_encode($response),
            ]);
               WalletService::refund($user, $txn->amount, $txn, $service);
               return ResponseHelper::error(
                $response['message'] ?? 'Transaction failed',
                $response,
                403
            );
        }
    }

     public static function handleBalanceEnquiry($txn, $response, $user)
    {
        if (isset($response)) {
            if ($response['statuscode'] == 'TXN') {
                $txn->update([
                    'rrn' => $response['data']['operatorId'],
                    'api_response'=> json_encode($response),
                    'status'      => 'SUCCESS'
                ]);
    
                return ResponseHelper::success(
                    'Account Balance Rs. ' . $response['data']['bankAccountBalance'],
                    $response["data"],200
                );
    
            } elseif ($response['statuscode'] == 'TUP') {
                $txn->update([
                    'rrn' => $response['status'],
                    'api_response'=> json_encode($response),
                    'status'      => 'PENDING'
                ]);
    
                return ResponseHelper::error(
                    $response['message'],
                    $response,403
                );
    
            } else {
                $txn->update([
                    'rrn' => $response['status'],
                    'api_response'=> json_encode($response),
                    'status'      => 'FAILED'
                ]);
    
                return ResponseHelper::error(
                    $response['message'],
                    $response,403
                );
            }
        } else {
            $txn->update([
                'rrn' => 'No Response',
                'status'      => 'PENDING'
            ]);
    
            return ResponseHelper::pending(
                'Something Went Wrong',403
            );
        }
    }
  public static function handleBalanceEnquiry2($txn, $response, $user)
    {
        if (isset($response)) {
            if ($response['statusCode'] == 10000  && $response['data']['responseCode'] == 00) {
                $txn->update([
                    'rrn' => $response['data']['bankRRN'],
                    'api_response'=> json_encode($response),
                    'status'      => 'SUCCESS'
                ]);
    
                return ResponseHelper::success(
                    'Account Balance Rs. ' . $response['data']['balanceAmount'],
                    $response["data"],200
                );
    
            } else {
                $txn->update([
                    'rrn' => $response['message'],
                    'api_response'=> json_encode($response),
                    'status'      => 'FAILED'
                ]);
    
                return ResponseHelper::error(
                    $response['message'],
                    $response,403
                );
            }
        } else {
            $txn->update([
                'rrn' => 'No Response',
                'status'      => 'PENDING'
            ]);
    
            return ResponseHelper::pending(
                'Something Went Wrong',403
            );
        }
    }
    // public static function handleCashWithdraw($txn, $response, $user, $service, $comm)
    // {
    //     if (isset($response)) {
    //         switch ($response['status']) {
    //             case 'SUCCESS':
    //                 $txn->update([
    //                     'rrn' => $response['operator_id'],
    //                     'api_response'=> json_encode($response),
    //                     'status'      => 'SUCCESS'
    //                 ]);
    //                 WalletService2::credit($user, $txn->amount, $txn, $service);
    //                 WalletService::creditCommission($user, $txn->comm, $txn, $service);
    //             $distributor = $user->parent ? User::find($user->parent) : null;
    //             if ($distributor && $txn->di_comm > 0) {
    //                 WalletService::creditCommission($distributor, $txn->di_comm, $txn, $service);
    //             }
    //             $masterDistributor = $distributor && $distributor->parent ? User::find($distributor->parent) : null;
    //             if ($masterDistributor && $txn->md_comm > 0) {
    //                 WalletService::creditCommission($masterDistributor, $txn->md_comm, $txn, $service);
    //             }
    //                 return ResponseHelper::success("Transaction Successful", $response, 200);
    
    //             case 'PENDING':
    //                 $txn->update([
    //                     'rrn' => $response['status'],
    //                     'api_response'=> json_encode($response),
    //                 ]);
    
    //                 return ResponseHelper::error($response['operator_id'], $response, 403);
    
    //             default:
    //                 $txn->update([
    //                     'rrn' => $response['operator_id'],
    //                     'api_response'=> json_encode($response),
    //                     'status'      => 'FAILED'
    //                 ]);
    
    //                 return ResponseHelper::error($response['operator_id'], $response, 403);
    //         }
    //     } else {
    //         $txn->update([
    //             'rrn' => 'No Response',
    //             'status'      => 'PENDING'
    //         ]);
    
    //         return ResponseHelper::error('Something went wrong', null, 403);
    //     }
    // }
    public static function handleCashWithdraw($txn, $response, $user, $service, $comm){
        $resp = $response;
        if (isset($resp)) {
            switch (strtoupper($resp['status'])) { 
                case 'SUCCESS':
                    $txn->update([
                        'rrn'          => $resp['operator_id'] ?? null,
                        'api_response' => json_encode($resp),
                        'status'       => 'SUCCESS'
                    ]);
    
                    WalletService2::credit($user, $txn->amount, $txn, $service);
                    WalletService2::creditCommission($user, $txn, $service);
    
                    $distributor = $user->parent ? User::find($user->parent) : null;
                    if ($distributor && $txn->di_comm > 0) {
                        WalletService2::creditCommission($distributor, $txn, $service);
                    }
    
                    $masterDistributor = $distributor && $distributor->parent ? User::find($distributor->parent) : null;
                    if ($masterDistributor && $txn->md_comm > 0) {
                        WalletService2::creditCommission($masterDistributor, $txn, $service);
                    }
    
                    return ResponseHelper::success("Transaction Successful", $resp, 200);
    
                case 'PENDING':
                    $txn->update([
                        'rrn'          => $resp['operator_id'] ?? 'Pending',
                        'api_response' => json_encode($resp),
                        'status'       => 'PENDING'
                    ]);
    
                    return ResponseHelper::error('Transaction Pending', $resp, 403);
                     case 'FAILED':
                     $txn->update([
                        'rrn'          => $resp['operator_id'] ?? 'Pending',
                       'api_response' => json_encode($resp),
                         'status'       => 'FAILED'
                                    ]);
                    
                    return ResponseHelper::error('Transaction Failed ', $resp, 403);
                    
                default:
                    $txn->update([
                        'rrn'          => $resp['operator_id'] ?? 'Pending',
                        'api_response' => json_encode($resp),
                        'status'       => 'PENDING'
                    ]);
    
                    return ResponseHelper::error('Transaction Pending', $resp, 403);
            }
        } else {
            $txn->update([
                'rrn'    => 'No Response',
                'status' => 'PENDING'
            ]);
    
            return ResponseHelper::error('Something went wrong', null, 403);
        }
    }
    public static function handleCashWithdraw2($txn, $response, $user, $service, $comm)
    {
        if (!$response) {
            $txn->update([
                'rrn'    => 'No Response',
                'status' => 'PENDING'
            ]);
    
            return ResponseHelper::error('Transaction under process', null, 201);
        }
    
        // SUCCESS CONDITION
        if (
            isset($response['statusCode']) &&
            $response['statusCode'] == 10000 &&
            isset($response['data']['responseCode']) &&
            $response['data']['responseCode'] == "00"
        ) {
    
            $txn->update([
                'rrn'          => $response['data']['bankRRN'] ?? null,
                'api_response' => json_encode($response),
                'status'       => 'SUCCESS'
            ]);
    
            // Commission + Credit
            WalletService2::credit($user, $txn->amount, $txn, $service);
            WalletService2::creditCommission($user, $txn, $service);
    
            // Distributor Commission
            $distributor = $user->parent ? User::find($user->parent) : null;
            if ($distributor && $txn->di_comm > 0) {
                WalletService2::creditCommission($distributor, $txn, $service);
            }
    
            // Master Distributor Commission
            $masterDistributor = ($distributor && $distributor->parent)
                ? User::find($distributor->parent)
                : null;
    
            if ($masterDistributor && $txn->md_comm > 0) {
                WalletService2::creditCommission($masterDistributor, $txn, $service);
            }
    
            return ResponseHelper::success(
                'Transaction With Amount Rs ' . $txn->amount .
                ' Is Successful. Remaining Balance Is Rs ' . $response['data']['balanceAmount']??"Transaction success" .
                '. Kindly Give Cash To Customer.',
                $response,
                200
            );
        }
        $txn->update([
            'rrn'          => $response['message'] ?? 'Failed',
            'api_response' => json_encode($response),
            'status'       => 'FAILED'
        ]);
    
        return ResponseHelper::error('Transaction Failed', $response, 403);
    }

    public static function handleMinistatement($txn, $response, $user, $service, $comm)
    {
        if (isset($response)) {
            switch ($response['statuscode']) {
                case 'TXN':
                    $txn->update([
                        'rrn' => $response['data']['operatorId'],
                        'api_response'=> json_encode($response),
                        'status'      => 'SUCCESS'
                    ]);
                    WalletService2::credit($user, $txn->amount, $txn, $service);
                   WalletService::creditCommission($user, $txn->comm, $txn, $service);
    
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
    
                    return ResponseHelper::success("Transaction Successful", $response, 200);
    
                case 'TUP':
                    $txn->update([
                        'rrn' => $response['status'],
                        'api_response'=> json_encode($response),
                        'status'      => 'PENDING'
                    ]);
    
                    return ResponseHelper::error($response['message'], $response, 403);
               case 'ERR':
                    $txn->update([
                        'rrn' => $response['status'],
                        'api_response'=> json_encode($response),
                        'status'      => 'FAILED'
                    ]);
                default:
                    $txn->update([
                        'rrn' => $response['status'],
                        'api_response'=> json_encode($response),
                        'status'      => 'PENDING'
                    ]);
    
                    return ResponseHelper::error($response['message'], $response, 403);
            }
        } else {
            $txn->update([
                'rrn' => 'No Response',
                 'api_response'=> 'No Response',
                'status'      => 'PENDING'
            ]);
    
            return ResponseHelper::error('Something Went Wrong', null, 403);
        }
    }
    public static function handleMinistatement2($txn, $response, $user, $service, $comm)
    {
        if (!isset($response)) {
    
            $txn->update([
                'rrn'          => 'No Response',
                'api_response' => 'No Response',
                'status'       => 'PENDING'
            ]);
    
            return ResponseHelper::error('Something Went Wrong', null, 403);
        }
    
        // SUCCESS CONDITION
        if ($response['statusCode'] == 10000 && $response['data']['responseCode'] == "00") {
    
            $txn->update([
                'rrn'          => $response['data']['bankRRN'] ?? null,
                'api_response' => json_encode($response),
                'status'       => 'SUCCESS'
            ]);
    
            // Commission
            WalletService2::credit($user, $txn->amount, $txn, $service);
            WalletService::creditCommission($user, $txn->comm, $txn, $service);
    
            // Distributor
            $distributor = $user->parent ? User::find($user->parent) : null;
            if ($distributor && $txn->di_comm > 0) {
                WalletService::creditCommission($distributor, $txn->di_comm, $txn, $service);
            }
    
            // Master Distributor
            $masterDistributor = $distributor && $distributor->parent ? User::find($distributor->parent) : null;
            if ($masterDistributor && $txn->md_comm > 0) {
                WalletService::creditCommission($masterDistributor, $txn->md_comm, $txn, $service);
            }
    
    return ResponseHelper::success(
    "Transaction Successful", ['miniStatement' => $response['data']['miniOffusStatementStructureModel'] == NULL? $response['data']['miniStatementStructureModel']: $response['data']['miniOffusStatementStructureModel'], 'bankAccountBalance' => $response['data']['balanceAmount'] ],
    200);

    
        // FAILED CONDITION
        $txn->update([
            'rrn'          => $response['message'] ?? null,
            'api_response' => json_encode($response),
            'status'       => 'PENDING'
        ]);
    
        return ResponseHelper::error($response['message'] ?? 'Failed', $response, 403);
    }

}

}