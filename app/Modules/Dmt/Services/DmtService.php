<?php
namespace App\Modules\Dmt\Services;

use App\Modules\Dmt\Models\DmtTxn;

use App\Modules\Dmt\Models\VerificationTxn;

class DmtService
{
    public static function create($user, $service, $request, $comm, $a_comm)
    {
        $dmtTxn = new DmtTxn();
        $dmtTxn->user_id = $user->id;
        $dmtTxn->txn_id = 'DMT' . time() . rand(100, 999);
        $dmtTxn->route = $service->route;
        $dmtTxn->client_ref = $request->client_ref;
        $dmtTxn->pf = $request->header('user-agent');
        $dmtTxn->sender_mobile = $request->mobile_number;
        $dmtTxn->beneficiary_name = $request->ben_name;
        $dmtTxn->account_number = $request->ben_acc;
        $dmtTxn->ifsc_code = $request->ifsc ?? null;
        $dmtTxn->bank_name = $request->bank_name ?? null;
        $dmtTxn->mop = $request->type;
        $dmtTxn->operator = $service->name;
        $dmtTxn->amount = $request->amount;
        $ccf = $request->amount > 1000 ? round($request->amount * 1 / 100, 2) : 10;
        $dmtTxn->gst = $ccf - $ccf * 100/118;
        $dmtTxn->ccf = $ccf;
        $ret_comm = $comm['ret'] ?? 0;
        $dd_comm = $comm['dd'] ?? 0;
        $di_comm = $comm['di'] ?? 0;
        $md_comm = $comm['md'] ?? 0;
        $dmtTxn->ret_comm = $user->role === 'ret' ? $ret_comm : ($user->role === 'dd' ? $dd_comm : 0);
        $dmtTxn->di_comm = $di_comm;
        $dmtTxn->md_comm = $md_comm;
        $dmtTxn->ret_tds = round($dmtTxn->ret_comm * 2 / 100, 2);
        $dmtTxn->di_tds = round($dmtTxn->di_tds * 2 / 100, 2);
        $dmtTxn->md_tds = round($dmtTxn->md_tds * 2 / 100, 2);
        $adminComm = $ccf-$dmtTxn->gst-$ret_comm-$di_comm-$md_comm-$a_comm;
        $dmtTxn->a_comm = $adminComm;
        // Location
        $dmtTxn->location = json_encode([
            'lat' => $request->latitude,
            'log' => $request->longitude,
            'ip'  => $request->ip(),
        ]);

        $dmtTxn->save();

        return $dmtTxn;
    }
    
    public static function createVerification($user, $service, $request, $comm,$a_comm)
    {
        $dmtTxn = new VerificationTxn();
        $dmtTxn->user_id          = $user->id;
        $dmtTxn->txn_id           = 'ACV' . time() . rand(100, 999);
        $dmtTxn->route            = $service->route ?? null;
        $dmtTxn->client_ref       = $request->client_ref ?? null;
        $dmtTxn->pf               = $request->header('user-agent');
        $dmtTxn->sender_mobile    = $request->number ?? $request->mobile_number; 
        $dmtTxn->beneficiary_name = $request->ben_name;
        $dmtTxn->account_number   = $request->ben_acc;
        $dmtTxn->ifsc_code        = $request->ifsc ?? null;
        $dmtTxn->bank_name        = $request->bank_name ?? null;
        $dmtTxn->mop              = "ACV";
        $dmtTxn->operator         = $service->name ?? 'ACV';
        $retComm   =  $comm['ret'] ??$comm['dd'];
        $gst       = $retComm * 18/100;
        $dmtTxn->gst     = $gst;
        $dmtTxn->charges = $retComm;
        $dmtTxn->a_comm = $retComm-$a_comm;
        $dmtTxn->amount  = 1;
        $dmtTxn->location = json_encode([
            'lat' => $request->latitude ?? null,
            'lng' => $request->longitude ?? null,
            'ip'  => $request->ip(),
        ]);
    
        $dmtTxn->save();
    
        return $dmtTxn;
    }
     public static function createKyc($user, $service, $request, $comm){
        $dmtTxn = new DmtTxn();
        $dmtTxn->user_id          = $user->id;
        $dmtTxn->txn_id           = 'DMT' . time() . rand(100, 999);
        $dmtTxn->route            = $service->route ?? null;
        $dmtTxn->client_ref       = $request->client_ref ?? null;
        $dmtTxn->pf               = $request->header('user-agent');
        $dmtTxn->sender_mobile    = $request->number ?? $request->mobile_number; // consistent with validation
        $dmtTxn->beneficiary_name = $request->ben_name??null;
        $dmtTxn->account_number   = $request->ben_acc??null;
        $dmtTxn->ifsc_code        = $request->ifsc ?? null;
        $dmtTxn->bank_name        = $request->bank_name ?? null;
        $dmtTxn->mop              = "DMT1KYC";
        $dmtTxn->operator         = $service->name ;
        $retComm   =  $comm['ret'] ??$comm['dd'];
        $gst       = $retComm - $retComm * 100/118;
        $dmtTxn->gst     = $gst;
        $dmtTxn->charges = $retComm;
        $dmtTxn->amount  = $retComm;
        $dmtTxn->location = json_encode([
            'lat' => $request->latitude ?? null,
            'lng' => $request->longitude ?? null,
            'ip'  => $request->ip(),
        ]);
    
        $dmtTxn->save();
    
        return $dmtTxn;
    }
    
}
