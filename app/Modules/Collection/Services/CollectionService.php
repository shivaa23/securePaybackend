<?php
namespace App\Modules\Collection\Services;

use App\Modules\Collection\Models\Collection3;

class CollectionService
{
    public static function createCollection($user, $service, $request, $comm,$a_comm)
    {
        $charge = $comm['ret'] ?? $comm['dd']??0;
        $txn = new Collection3();
        $txn->user_id        = $user->id;
        $txn->txn_id         = 'QR' . time() . rand(100, 999);
        $txn->client_ref     = $request->client_ref ?? null;
        $txn->route          = $service->route;
        $txn->pf             = $request->header('user-agent');
        $txn->customer_mobile = $request->customer_mobile;
        $txn->amount         = $request->amount;
        $txn->charges        = ($comm['rule_type'] == 'charge') ? $charge : 0;
        $txn->ret_comm     = ($comm['rule_type'] == 'commission') ? $charge : 0;
        $txn->di_comm=$comm['di']??0;
        $txn->md_comm=$comm['md']??0;
        $txn->operator = $service->name;
        $txn->a_comm = $charge - $txn->di_comm - $txn->md_comm + $a_comm;
        // if(in_array($service->code ,['C04','C05','C15','C14','C07','C02','C08'])){
        //     $txn->a_comm = $charge - $txn->di_comm - $txn->md_comm;
        // }elseif($service->code == 'C05'){
        //     $txn->a_comm = $charge - $txn->di_comm - $txn->md_comm;
        // }
        $txn->gst            = $txn->charges - ($txn->charges / 1.18);
        $txn->ret_tds        = $txn->ret_comm *2/100;
        $txn->di_tds         = $txn->di_comm *2/100;
        $txn->md_tds         = $txn->md_comm *2/100;
        $txn->save();

        return $txn;
    }
}
