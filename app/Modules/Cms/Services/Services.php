<?php
namespace App\Modules\Payout\Services;

use App\Modules\CMS\Models\CmsTxn;

class CmsService
{
    public static function create($user, $service, $request, $comm, $a_comm)
    {
        $latitude  = 22.1234 ;
        $longitude = 72.1234 ;

        $cmsTxn = new CmsTxn();
        $cmsTxn->user_id       = $user->id;
        $cmsTxn->txn_id        = 'CMS' . time() . rand(100, 999);
        $cmsTxn->route         = $service->route;
        $cmsTxn->client_ref    = $request->refid;
        $cmsTxn->pf            = $request->header('user-agent');
        $cmsTxn->mobile_number = $request->mobile_no;
        $cmsTxn->biller_name   = $request->biller_name;
        $cmsTxn->biller_id     = $request->biller_id;
        $cmsTxn->operator      = $service->name;
        $cmsTxn->amount        = $request->amount;
        $cmsTxn->charges  = $comm['ret'] ?? $comm['dd'];
        $cmsTxn->gst      = $cmsTxn->charges - $cmsTxn->charges / 1.18;
        $cmsTxn->di_comm  = $comm['di'] ?? 0;
        $cmsTxn->md_comm  = $comm['md'] ?? 0;

        $cmsTxn->di_tds   = $cmsTxn->di_comm * 0.02;
        $cmsTxn->md_tds   = $cmsTxn->md_comm * 0.02;
        $cmsTxn->a_comm   = $a_comm;
        $cmsTxn->location = json_encode([
            'latitude' => $latitude,
            'longtude' => $longitude,
            'ip'  => $request->ip()
        ]);

        $cmsTxn->save();

        return $cmsTxn;
    }
}
