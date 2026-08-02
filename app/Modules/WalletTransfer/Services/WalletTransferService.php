<?php

namespace App\Modules\WalletTransfer\Services;

use App\Modules\WalletTransfer\Models\WalletTransaction;
use Auth;
class WalletTransferService
{
    public static function create($sender, $receiver, $service, $request)
    {
        $user=Auth::user();
        // if($user->role="adm"){
        
        // }
        $transfer = new WalletTransaction();
        $transfer->user_id     = $sender->id;
        $transfer->reciever_id  = $receiver->id;
        $transfer->sender_est     = $sender->establishment;
        $transfer->receiver_est  = $receiver->establishment;
        $transfer->txn_id    = 'W2W' . time() . rand(1000, 9999);
        $transfer->client_ref  = $request->client_ref;
        $transfer->amount      = $request->amount;
        $transfer->remark      = $request->remark;
        $transfer->route       = $service->route;
        $transfer->operator       = $service->name;
        $transfer->status       = "success";
        $transfer->pf          = $request->header('user-agent');
        $transfer->charge       = 0;
        $transfer->gst = 0;
        $transfer->location    = json_encode([
            'lat' => $request->latitude,
            'log' => $request->longitude,
            'ip'  => $request->ip(),
        ]);
        $transfer->save();
        //$receiver->incrementW1($request->amount);

        return $transfer;
    }
    public static function createW2W1($sender,$service, $request, $comm)
    {
        $transfer = new WalletTransaction();
        $transfer->user_id     = $sender->id;
        $transfer->reciever_id  = $sender->id;
        $transfer->sender_est     = $sender->establishment;
        $transfer->txn_id    = 'W2W1' . time() . rand(1000, 9999);
        $transfer->client_ref  = $request->client_ref;
        $transfer->amount      = $request->amount;
        $transfer->remark      = $request->remark;
        $transfer->route       = $service->route;
        $transfer->operator       = $service->name;
        $transfer->status       = "success";
        $transfer->pf          = $request->header('user-agent');
        $transfer->gst         = round($request->amount * 0.18, 2);
        $transfer->charge       = $sender->role === 'ret' ? ($comm['ret'] ?? 0) : ($sender->role === 'dd' ? ($comm['dd'] ?? 0) : 0);
        $transfer->gst = $sender->role === 'ret' ? ($comm['ret'] ?? 0) : ($sender->role === 'dd' ? ((($comm['dd'] ?? 0) > 0) ? round($request->amount * 0.18, 2) : 0) : 0);
        $transfer->location    = json_encode([
            'lat' => $request->latitude,
            'log' => $request->longitude,
            'ip'  => $request->ip(),
        ]);
        $transfer->save();

        return $transfer;
    }
        public static function createW3W1($sender,$service, $request, $comm)
    {
        $transfer = new WalletTransaction();
        $transfer->user_id     = $sender->id;
        $transfer->reciever_id  = $sender->id;
        $transfer->txn_id    = 'W3W1' . time() . rand(1000, 9999);
        $transfer->client_ref  = $request->client_ref;
        $transfer->amount      = $request->amount;
        $transfer->remark      = $request->remark;
        $transfer->route       = $service->route;
        $transfer->operator       = $service->name;
        $transfer->status       = "success";
        $transfer->pf          = $request->header('user-agent');
        $transfer->gst         = round($request->amount * 0.18, 2);
        $transfer->charge       = $sender->role === 'ret' ? ($comm['ret'] ?? 0) : ($sender->role === 'dd' ? ($comm['dd'] ?? 0) : 0);
        $transfer->gst = $sender->role === 'ret' ? ($comm['ret'] ?? 0) : ($sender->role === 'dd' ? ((($comm['dd'] ?? 0) > 0) ? round($request->amount * 0.18, 2) : 0) : 0);
        $transfer->location    = json_encode([
            'lat' => $request->latitude,
            'log' => $request->longitude,
            'ip'  => $request->ip(),
        ]);
        $transfer->save();

        return $transfer;
    }
}
