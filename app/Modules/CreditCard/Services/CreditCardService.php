<?php
namespace App\Modules\CreditCard\Services;

use App\Modules\CreditCard\Models\CreditCard;

class CreditCardService
{
    public static function create($user, $service, $request, $comm)
    {
        $charge = $comm['ret'] ?? $comm['dd'];
        $txn = new CreditCard();
        $txn->user_id        = $user->id;
        $txn->txn_id         = 'CARD' . time() . rand(100, 999);
        $txn->client_ref     = $request->client_ref ?? null;
        $txn->route          = $service->route;
        $txn->pf             = $request->header('user-agent');
        $txn->name = $request->name; 
        $txn->cnumber = $request->cnumber;
        $txn->operator = $service->name; 
        $txn->number = $request->number ;
        $txn->amount         = $request->amount;
        $txn->charges        = ($comm['rule_type'] == 'charge') ? $charge : 0;
        $txn->ret_comm     = ($comm['rule_type'] == 'commission') ? $charge : 0;
        $txn->di_comm=$comm['di']??0;
        $txn->md_comm=$comm['md']??0;
         $txn->location = json_encode([
            'lat' => $request->latitude,
            'log' => $request->longitude,
            'ip'  => $request->ip(),
        ]);

        $txn->gst            = $txn->charges - ($txn->charges / 1.18);
        $txn->ret_tds            = $txn->ret_comm *2/100;
        $txn->di_tds         = $txn->di_comm *2/100;
        $txn->md_tds         = $txn->md_comm *2/100;
        $txn->save();

        return $txn;
    }
}
