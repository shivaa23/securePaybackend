<?php
namespace App\Modules\Cms\Services;
use App\Modules\Cms\Models\CmsTxn;
class CmsService
{
 public static function create($user, $service, $request, $comm, $a_comm)
{
    $latitude  = 22.1234;
    $longitude = 72.1234;

    $cmsTxn = new CmsTxn();
    $cmsTxn->user_id = $user->id;
    $cmsTxn->txn_id = 'CMS' . time() . rand(100, 999);
    $cmsTxn->route = $service->route;
    $cmsTxn->client_ref = $request['refid'] ?? null;
    $cmsTxn->mobile_number = $request['mobile_no'] ?? null;
    $cmsTxn->biller_name = $request['biller_name'] ?? null;
    $cmsTxn->biller_id = $request['biller_id'] ?? null;
    $cmsTxn->pf = request()->header('user-agent');
    $cmsTxn->operator = $service->name;
    $cmsTxn->amount = $request['amount'];
    $cmsTxn->gst = $cmsTxn->charges - ($cmsTxn->charges / 1.18);
    $cmsTxn->ret_comm = $comm['ret'] ?? $comm['dd'];
    $cmsTxn->di_comm = $comm['di'] ?? 0;
    $cmsTxn->di_tds = $cmsTxn->di_comm * 0.02;
    $cmsTxn->ret_tds = $cmsTxn->ret_comm * 0.02;
    $cmsTxn->md_tds = $cmsTxn->md_comm * 0.02;
    $cmsTxn->a_comm = $a_comm;
    $cmsTxn->location = json_encode([
        'lat' => $latitude,
        'log' => $longitude,
        'ip'  => request()->ip(),
    ]);

    $cmsTxn->save();

    return $cmsTxn;
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
            'lat' => $request->latitude ?? 22.1234,
            'log' => $request->longitude ?? 72.1234,
            'ip'  => $request->ip(),
        ]);
        $payoutTxn->save();
        
        return $payoutTxn;
    }
}