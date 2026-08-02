<?php
namespace App\Modules\Recharge\Services;
use App\Modules\Recharge\Models\RechargeTxn;
class RechargeService
{
    public static function create($user, $service, $request, $comm,$a_comm)
    {
        // return $a_comm;
        $rechargeTxn = new RechargeTxn();
        $rechargeTxn->user_id = $user->id;
        $rechargeTxn->txn_id = 'RCH' . time() . rand(100, 999);
        $rechargeTxn->route = $service->route;
        $rechargeTxn->client_ref = $request->client_ref;
        $rechargeTxn->pf = $request->header('user-agent');
        $rechargeTxn->mobile_number = $request->mobile_number;
        $rechargeTxn->operator = $service->name;
        $rechargeTxn->amount = $request->amount;
        $rechargeTxn->gst = round($request->amount * 0.18, 2);
        $rechargeTxn->ret_comm = $user->role === 'ret' ? $comm['ret'] : ($user->role === 'dd' ? $comm['dd'] : 0);
        $rechargeTxn->di_comm = $comm['di'] ?? 0;
        $rechargeTxn->md_comm = $comm['md'] ?? 0;
        $rechargeTxn->a_comm = $a_comm - $rechargeTxn->di_comm - $rechargeTxn->md_comm - $rechargeTxn->ret_comm;
        // return $rechargeTxn->a_comm;
        $rechargeTxn->di_tds = $rechargeTxn->di_comm*0.02 ;
        $rechargeTxn->md_tds = $rechargeTxn->md_comm*0.02 ;
        $rechargeTxn->location = json_encode([
            'lat' => $request->latitude,
            'log' => $request->longitude,
            'ip'  => $request->ip(),
        ]);
        $rechargeTxn->save();
        return $rechargeTxn;
    }
}