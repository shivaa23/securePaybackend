<?php
namespace App\Modules\Dmt\Services;

use App\Modules\WalletLedger\Services\WalletService;
use App\Helper\ResponseHelper;
use App\Models\User;
use App\Modules\Dmt\Models\DmtBeneficiarie;
use Carbon\Carbon;
use App\Modules\Template\Models\Template;
use App\Modules\Message\Models\Sms;
class DMT2ResponseHandler
{
    public static function handle($txn, $response, $user, $service, $comm, $request = null)
    {
        if (!isset($response['response_code'])) {
            $txn->update([
                'status'       => 'PENDING',
                'api_response' => json_encode(['error' => 'No response from API']),
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
    

            return ResponseHelper::error('Txn Pending', 'No response from API', 500);
        }
        if ($response['response_code'] == 1) {
            if ($response['txn_status'] == 1) {
                $txn->update([
                    'status'       => 'SUCCESS',
                    'operator_id'        => $response['utr'] ?? null,
                    'api_response' => json_encode($response),
                ]);
            WalletService::creditCommission($user, $txn->comm, $txn, $service);
          $template = Template::where('name', 'PSPRINT')->first();
        if (!$template) {
            return $this->jsonResponse('1001', 'Template not found', null, null, 500);
        }
        $message = $template->message;
        $message = str_replace('{$amount}', $request->amount ?? $txn->amount, $message);
        $message = str_replace('{$ben_name}', $txn->beneficiary_name ?? '', $message);
        $message = str_replace('{$beneAccount}', $txn->account_number ?? '', $message);
        $message = str_replace('{$rrn}', $txn->operator_id ?? '', $message);
        $data = [
            "number"  => $txn->sender_mobile,
            "message" => $message,
            "tempid"  => $template->temp_id,
        ];
        
        Sms::send($data, $user);
            return ResponseHelper::success(
          'Request Processed Successfully With RRN ' . $response['utr'],
          $response['utr'],
    200
);


            } elseif (in_array($response['txn_status'], [2, 3, 4])) {
                $txn->update([
                    'status'       => 'PENDING',
                    'operator_id'        => 'Transaction Under Process',
                    'api_response' => json_encode($response),
                ]);
                return ResponseHelper::success('Transaction Under Process', $response, 201);
            } else {
               
             WalletService::refund($user, $txn->amount, $txn, $service);

                $txn->update([
                    'status'       => 'FAILED',
                    'operator_id'        => $response['remarks'] ?? 'FAILED',
                    'api_response' => json_encode($response),
                ]);

                return ResponseHelper::error('Transaction Failed', $response['remarks'] ?? 'Failed', 403);
            }
        } elseif (in_array($response['response_code'], [11, 16, 8])) {
            WalletService::refund($user, $txn->amount, $txn, $service);

            $txn->update([
                'status'       => 'FAILED',
                'operator_id'        => $response['message'] ?? 'FAILED',
                'api_response' => json_encode($response),
            ]);

            return ResponseHelper::error('Transaction Failed', $response['message'] ?? 'Failed', 403);
        } else {
            WalletService::refund($user, $txn->amount, $txn, $service);

            $txn->update([
                'status'       => 'PENDING',
                'operator_id'        => 'NA',
                'api_response' => json_encode($response),
            ]);

            return ResponseHelper::error('Transaction Failed', $response['message'] ?? 'Failed', 403);
        }
    }
}
