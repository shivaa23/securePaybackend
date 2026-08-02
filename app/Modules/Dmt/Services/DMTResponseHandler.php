<?php
namespace App\Modules\Dmt\Services;

use App\Modules\WalletLedger\Services\WalletService;
use App\Helper\ResponseHelper;
use App\Modules\User\Models\User;
use App\Models\Report;
use App\Modules\Dmt\Models\DmtBeneficiarie;
use Carbon\Carbon;
use Auth;
use Illuminate\Support\Facades\Date;
use App\Modules\Misc\Models\Uuid;
class DMTResponseHandler
{
    public static function handle($txn, $response, $user, $service, $comm)
    {
        $statusCode = strtoupper($response['statuscode'] ?? $response['data']['statuscode'] ?? null);
    
        if (!$statusCode) {
            $txn->update([
                'status' => 'PENDING',
                'api_response' => json_encode(['error' => 'No response from API']),
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
    
            return ResponseHelper::error('Txn Failed', 'No response from API', 500);
        }
    
        switch ($statusCode) {
            case 'TXN':
                $txn->update([
                    'status' => 'SUCCESS',
                    'operator_id' => $response['data']['txnReferenceId'] ?? null,
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
    
                return ResponseHelper::success(
                    'Transaction Successful',
                    [
                        'rrn' => $response['data']['txnReferenceId'] ?? null,
                        'message' => $response['status'] ?? 'Processed successfully'
                    ],
                    200
                );
            case 'ERR':
                
        // if($user->id==5){
        $time = Date::now()->format('ymdHis'); 
        $random = mt_rand(10000, 99999); 
        $uuid = $time . $random;
        $data = Uuid::create([
            'uuid' => $uuid,
            'status' => 1,
            'user_id' =>$user->id,
        ]);
        $responseData=[];
            $responseData['uuid']=$data['uuid'];
             $responseData['status']=$response['status'];
            return ResponseHelper::error(
                    $response['message'] ?? 'Transaction failed',
                    $responseData?? 'Transaction failed',
                    403
                );
        // }
          
            case 'SPE':
            case 'IAN':
            case 'SPD':
            case 'ISE':
                WalletService::refund($user, $txn->amount, $txn, $service);
                $txn->update([
                    'status' => 'FAILED',
                    'api_response' => json_encode($response),
                ]);
    
    
                return ResponseHelper::error(
                    $response['message'] ?? 'Transaction Failed',
                    $response['status'] ?? 'Transaction failed',
                    403
                );
    
           
            case 'TUP':
                $txn->update([
                    'status' => 'PENDING',
                    'operator_id' => $response['status'] ?? null,
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
    
                return ResponseHelper::success(
                    $response['message'] ?? 'Transaction Pending',
                    $response,
                    202
                );
    
        
            default:
                $txn->update([
                    'status' => 'PENDING',
                    'operator_id' => $response['status'] ?? null,
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
    
                return ResponseHelper::success(
                    'Transaction Pending',
                    $response,
                    202
                );
        }
    }

//     public static function handleVerification($txn, $response, $user,$service,$comm)
//     {
//         $user=Auth::user();
//         if (!isset($response['statuscode'])) {
//             $txn->update([
//                 'status'       => 'FAILED',
//                 'api_response' => json_encode(['error' => 'No response from API']),
//             ]);
    
//             // Refund wallet if txn fails
//             WalletService::refund($user, $txn->amount, $txn, $service);
    
//             return ResponseHelper::error('FAILED', 'Something Went Wrong', 403);
//         }
    
//         $statusCode = $txn->route=="ENKASH"? $response['data']['status']:strtoupper($response['statuscode']);
//       if($txn->route=="ENKASH"){
//         switch ($statusCode) {
//             case 'SUCCESS':
//                 $txn->update([
//                     'status'       => 'SUCCESS',
//                     'operator_id'        => $response['data']['txnReferenceId'] ?? null,
//                     'beneficiary_name'     => $response['data']['beneficiaryName'] ,
//                     'api_response' => json_encode($response),
//                     'is_verified'      => Carbon::now()->format('Y-m-d H:i:s')
//                 ]);
              
//                 // Update beneficiary record
//                 DmtBeneficiarie::where('account_number', $txn->account_number)
//                     ->where('ifsc_code', $txn->ifsc_code)
//                     ->update([
//                         'beneficiary_name' => $response['data']['beneficiaryName'] ,
//                         'is_active'           => 1,
//                         'is_verified'      => Carbon::now()->format('Y-m-d H:i:s'),
//                     ]);
    
//                 return ResponseHelper::success(
//                     $response['data']['beneficiaryName'],
//                     $response['data']['beneficiaryName'],
//                     200
//                 );
    
//             case 'FAILED':
//             case 'IAN':
//             case 'SPD':
//             case 'SPE':
//                 $txn->update([
//                     'status'       => 'FAILED',
//                     'operator_id'        => $response['status'] ?? 'Failed',
//                     'api_response' => json_encode($response),
//                 ]);
    
//                 // Refund wallet if txn fails
//                 WalletService::refund($user, $txn->amount, $txn, $service);
    
//                 return ResponseHelper::error(
//                   "Transaction Failed",
//                     $response['status'] ?? 'Verification Failed',
//                     403
//                 );
    
//             case 'TUP':
//                 $txn->update([
//                     'status'       => 'PENDING',
//                     'operator_id'        => $response['status'] ?? null,
//                     'api_response' => json_encode($response),
//                 ]);
    
//                 return ResponseHelper::success(
//                   " Transaction Pending",
//                     $response,
//                     202
//                 );
    
//             default:
//                 $txn->update([
//                     'status'       => 'PENDING',
//                     'operator_id'        => $response['status'] ?? null,
//                     'api_response' => json_encode($response),
//                 ]);
    
//                 return ResponseHelper::success(
//                   "Transaction Pending",
//                     $response,
//                     202
//                 );
//         }
//       }
//         switch ($statusCode) {
//     case 'TXN':
//         $txn->update([
//             'status'           => 'SUCCESS',
//             'operator_id'      => $response['data']['txnReferenceId'] ?? null,
//             'beneficiary_name' => $response['data']['beneficiaryName'] ?? $txn->beneficiary_name,
//             'api_response'     => json_encode($response),
//         ]);

      
//             DmtBeneficiarie::where('account_number', $txn->account_number)
//             //   ->where('mobile_number', $txn->sender_mobile)
//                 ->where('ifsc_code', $txn->ifsc_code)
//                 ->update([
//                     'beneficiary_name' => $response['data']['beneficiaryName'] ?? $txn->beneficiary_name,
//                     'is_active'        => 1,
//                     'is_verified'      => \Carbon\Carbon::now(),
//                 ]);
        

//         return ResponseHelper::success(
//             $response['data']['beneficiaryName'] ?? null,
//             $response['data']['beneficiaryName'] ?? null,
//             200
//         );

//     case 'ERR':
//     case 'IAN':
//     case 'SPD':
//     case 'SPE':
//         $txn->update([
//             'status'       => 'FAILED',
//             'operator_id'  => $response['status'] ?? 'Failed',
//             'api_response' => json_encode($response),
//         ]);

//         // Refund wallet if transaction fails
//         WalletService::refund($user, $txn->amount, $txn, $service);

//         return ResponseHelper::error(
//             $response['message'] ?? 'Transaction Failed',
//             $response['status'] ?? 'Verification Failed',
//             403
//         );

//     case 'TUP':
//         $txn->update([
//             'status'       => 'PENDING',
//             'operator_id'  => $response['status'] ?? null,
//             'api_response' => json_encode($response),
//         ]);

//         return ResponseHelper::success(
//             $response['message'] ?? 'Transaction Pending',
//             $response,
//             202
//         );

//     default:
//         $txn->update([
//             'status'       => 'PENDING',
//             'operator_id'  => $response['status'] ?? null,
//             'api_response' => json_encode($response),
//         ]);

//         return ResponseHelper::success(
//           'Transaction Pending',
//             $response,
//             202
//         );
// }
    
//     }
     public static function handleVerification($txn, $response, $user, $service, $comm)
    {
        $user = Auth::user();
    
        if ($txn->route !== "ENKASH" && !isset($response['statuscode'])) {
    
            $txn->update([
                'status'       => 'PENDING',
                'api_response' => json_encode(['error' => 'No response from API']),
            ]);
    
           
    
            return ResponseHelper::error('PENDING', 'Something Went Wrong', 403);
        }
    
        if ($txn->route == "ENKASH" &&
            !isset($response['data']['status']) &&
            !isset($response['status'])) {
    
            $txn->update([
                'status'       => 'FAILED',
               'operator_id'       =>$response['message'],
                'api_response' => json_encode($response),
            ]);
    
            WalletService::refund($user, $txn->amount, $txn, $service);
    
            return ResponseHelper::error($response['message'], [], 403);
        }
    
        if ($txn->route == "ENKASH") {
    
            $statusCode = strtoupper(
                $response['data']['status']
                ?? $response['status']
                ?? null
            );
    
        } else {
    
            $statusCode = strtoupper($response['statuscode']);
        }
    
        if ($txn->route == "ENKASH") {
    
            switch ($statusCode) {
    
                case 'SUCCESS':
                    $txn->update([
                        'status'            => 'SUCCESS',
                        'operator_id'       => $response['data']['txnReferenceId'] ?? null,
                        'beneficiary_name'  => $response['data']['beneficiaryName'] ?? null,
                        'api_response'      => json_encode($response),
                        'is_verified'       => Carbon::now()->format('Y-m-d H:i:s'),
                    ]);
    
                    DmtBeneficiarie::where('account_number', $txn->account_number)
                        ->where('ifsc_code', $txn->ifsc_code)
                        ->update([
                            'beneficiary_name' => $response['data']['beneficiaryName'] ?? null,
                            'is_active'        => 1,
                            'is_verified'      => Carbon::now(),
                        ]);
    
                    return ResponseHelper::success(
                        $response['data']['beneficiaryName'],
                        $response['data']['beneficiaryName'],
                        200
                    );
    
                case 'FAILED':
                case 'IAN':
                case 'SPD':
                case 'SPE':
    
                    $txn->update([
                        'status'       => 'FAILED',
                        'operator_id'  => $response['status'] ?? 'Failed',
                        'api_response' => json_encode($response),
                    ]);
    
                    WalletService::refund($user, $txn->amount, $txn, $service);
    
                    return ResponseHelper::error(
                        "Transaction Failed",
                        $response['status'] ?? 'Verification Failed',
                        403
                    );
    
                case 'TUP':
                    $txn->update([
                        'status'       => 'PENDING',
                        'operator_id'  => $response['status'] ?? null,
                        'api_response' => json_encode($response),
                    ]);
    
                    return ResponseHelper::success(
                        "Transaction Pending",
                        $response,
                        202
                    );
    
                default:
                    $txn->update([
                        'status'       => 'PENDING',
                        'operator_id'  => $response['status'] ?? null,
                        'api_response' => json_encode($response),
                    ]);
    
                    return ResponseHelper::success(
                        "Transaction Pending",
                        $response,
                        202
                    );
            }
        }
    
        switch ($statusCode) {
    
            case 'TXN':
                $txn->update([
                    'status'           => 'SUCCESS',
                    'operator_id'      => $response['data']['txnReferenceId'] ?? null,
                    'beneficiary_name' => $response['data']['beneficiaryName'] ?? $txn->beneficiary_name,
                    'api_response'     => json_encode($response),
                ]);
    
                DmtBeneficiarie::where('account_number', $txn->account_number)
                    ->where('ifsc_code', $txn->ifsc_code)
                    ->update([
                        'beneficiary_name' => $response['data']['beneficiaryName'] ?? $txn->beneficiary_name,
                        'is_active'        => 1,
                        'is_verified'      => Carbon::now(),
                    ]);
    
                return ResponseHelper::success(
                    $response['data']['beneficiaryName'] ?? null,
                    $response['data']['beneficiaryName'] ?? null,
                    200
                );
    
            case 'ERR':
            case 'IAN':
            case 'SPD':
            case 'SPE':
    
                $txn->update([
                    'status'       => 'FAILED',
                    'operator_id'  => $response['status'] ?? 'Failed',
                    'api_response' => json_encode($response),
                ]);
    
                WalletService::refund($user, $txn->amount, $txn, $service);
    
                return ResponseHelper::error(
                    $response['message'] ?? 'Transaction Failed',
                    $response['status'] ?? 'Verification Failed',
                    403
                );
    
            case 'TUP':
                $txn->update([
                    'status'       => 'PENDING',
                    'operator_id'  => $response['status'] ?? null,
                    'api_response' => json_encode($response),
                ]);
    
                return ResponseHelper::success(
                    $response['message'] ?? 'Transaction Pending',
                    $response,
                    202
                );
    
            default:
                $txn->update([
                    'status'       => 'PENDING',
                    'operator_id'  => $response['status'] ?? null,
                    'api_response' => json_encode($response),
                ]);
    
                return ResponseHelper::success(
                    'Transaction Pending',
                    $response,
                    202
                );
        }
    }

    
    public static function handleKycRemitter($txn, $response, $user, $service, $comm)
    {
        if (!isset($response['statuscode'])) {
            $txn->update([
                'api_response' => json_encode(['error' => 'No response from API']),
            ]);
            return ResponseHelper::error('FAILED', 'Something Went Wrong', 403);
        }
    
        $statusCode = strtoupper($response['statuscode']);
    //   return $statusCode;
        switch ($statusCode) {
            case 'TXN':
                $txn->update([
                    'status'            => 'SUCCESS',
                    'api_response'      => json_encode($response),
                ]);
            
                return ResponseHelper::success(
                    $response["message"],
                    $response,
                    200
                );
    
            case 'ERR':
            case 'SPD':
            case 'SPE':
            case 'IST':
            case 'IAN':
                // Calculate remitter closing balance
    
                $txn->update([
                    'status'       => 'FAILED',
                    'operator_id'  => $response['status'] ?? 'Failed',
                    'api_response' => json_encode($response),
                ]);
                WalletService::refund($user, $txn->amount, $txn, $service);
                return ResponseHelper::error(
                    $response["message"],
                    $response,
                    403
                );
    
            case 'TUP':
            default:
                $txn->update([
                    'operator_id'  => $response['status'] ?? null,
                    'api_response' => json_encode($response),
                ]);
    
                return ResponseHelper::success(
                    $response["message"],
                    $response,
                    202
                );
        }
    }

}
