<?php
namespace App\Modules\Payout\Services;
use App\Modules\Payout\Models\PayoutTxn;
use App\Modules\Collection\Models\Collection2;
class PayoutService
{
    public static function create($user, $service, $request, $comm, $a_comm)
    {
        
        $payoutTxn = new PayoutTxn();
        $payoutTxn->user_id = $user->id;
        $payoutTxn->txn_id = 'PMT' . time() . rand(100, 999);
        $payoutTxn->route = $service->route;
        $payoutTxn->client_ref = $request->client_ref;
        $payoutTxn->pf = $request->header('user-agent');
        $payoutTxn->mobile_number = $request->mobile_number;
        $payoutTxn->beneficiary_name = $request->beneficiary_name;
        $payoutTxn->account_number = $request->account_number;
        $payoutTxn->ifsc_code = $request->ifsc_code ?? null;
        $payoutTxn->bank_name = $request->bank_name ?? null;
        $payoutTxn->mop = $request->mop;
        $payoutTxn->operator = $service->name;
        $payoutTxn->charges = $comm['ret'] ?? $comm['dd'];
        $payoutTxn->amount = $request->amount;
        $payoutTxn->purpose = $request->purpose;
        $payoutTxn->gst = $payoutTxn->charges - $payoutTxn->charges / (1 + (18 / 100));
        $payoutTxn->di_comm = $comm['di'] ?? 0;
        $payoutTxn->md_comm = $comm['md'] ?? 0;
        $payoutTxn->di_tds = $payoutTxn->di_comm * 0.02;
        $payoutTxn->md_tds = $payoutTxn->md_comm * 0.02;
        $adminComm = $payoutTxn->charges - $a_comm - $payoutTxn->di_comm - $payoutTxn->md_comm;
        $payoutTxn->a_comm = $adminComm;
        $payoutTxn->location = json_encode([
            'lat' => $request->latitude,
            'log' => $request->longitude,
            'ip'  => $request->ip(),
        ]);
        $payoutTxn->save();
        
        return $payoutTxn;
    }
     public static function upiVerify($user, $service, $request, $comm,$totalAmount)
    {
        
        $payoutTxn = new PayoutTxn();
        $payoutTxn->user_id = $user->id;
        $payoutTxn->txn_id = 'PMT' . time() . rand(100, 999);
        $payoutTxn->route = $service->route;
        $payoutTxn->client_ref = $request->client_ref;
        $payoutTxn->pf = $request->header('user-agent');
        $payoutTxn->mobile_number = $request->mobile_number;
        $payoutTxn->beneficiary_name = $request->beneficiary_name;
        $payoutTxn->account_number = $request->account_number;
        $payoutTxn->ifsc_code = $request->ifsc_code ?? null;
        $payoutTxn->bank_name = $request->bank_name ?? null;
        $payoutTxn->mop = "UPI";
        $payoutTxn->operator = $service->name;
        $payoutTxn->charges = $comm['ret'] ?? $comm['dd'];
        $payoutTxn->amount = $totalAmount;
        
        $payoutTxn->gst = $payoutTxn->charges - $payoutTxn->charges / (1 + (18 / 100));
        $payoutTxn->location = json_encode([
            'lat' => $request->latitude,
            'log' => $request->longitude,
            'ip'  => $request->ip(),
        ]);
        $payoutTxn->save();
        
        return $payoutTxn;
    }
    public static function createCollection($user, $service, $request, $comm,$a_comm)
    {
        $charge = $comm['ret'] ?? $comm['dd'];
        $txn = new Collection2();
        $txn->user_id        = $user->id;
        $txn->txn_id         = 'QR' . time() . rand(100, 999);
        $txn->client_ref     = $request->client_ref ?? null;
        $txn->route          = $service->route;
        $txn->pf             = $request->header('user-agent');
        $txn->customer_mobile = $request->param2 ?? $user->mobile;
        $txn->amount         = $request->amount;
        $txn->charges        = ($comm['rule_type'] == 'charge') ? $charge : 0;
        $txn->ret_comm     = ($comm['rule_type'] == 'commission') ? $charge : 0;
        $txn->di_comm=$comm['di']??0;
        $txn->md_comm=$comm['md']??0;
        $txn->operator = $service->name;
        $txn->a_comm = $charge - $a_comm;
        // if(in_array($service->code ,['C04','C05','C15','C14','C07','C02','C08'])){
        //     $txn->a_comm = $charge - $txn->di_comm - $txn->md_comm;
        // }elseif($service->code == 'C05'){
        //     $txn->a_comm = $charge - $txn->di_comm - $txn->md_comm;
        // }
        $txn->gst            = $txn->charges - ($txn->charges / 1.18);
        $txn->ret_tds            = $txn->ret_comm *2/100;
        $txn->di_tds         = $txn->di_comm *2/100;
        $txn->md_tds         = $txn->md_comm *2/100;
        $txn->save();

        return $txn;
    }
}