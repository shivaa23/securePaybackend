<?php
namespace App\Modules\WalletLedger\Services;
use App\Modules\WalletLedger\Models\WalletLedger;
use App\Modules\User\Models\User;
class WalletService
{
    public static function debit($user, $amount, $txn, $service){
        if($service->type == 'AMTCHG'){
            $debit = ($amount + $txn->charges) * 100;
        }elseif($service->type == 'CHG'){
            $debit = ($txn->charges) * 100;
        }elseif($service->type == 'AMTCCF'){
            $debit = ($amount + $txn->ccf) * 100;
        }else{
            $debit = $amount * 100;
        }
        $lockedUser = User::where('id', $user->id)->lockForUpdate()->first();
        $opening = $lockedUser->w1 / 100;
        // Check balance before decrement
        if ($lockedUser->w1 < $debit) {
            throw new Exception('Insufficient balance. Transaction cannot proceed.');
        }
        
        $lockedUser->decrementW1($debit);
        $lockedUser->refresh();
        if ($lockedUser->w1 < 0) {
            throw new \Exception('Wallet integrity error: negative balance detected. Transaction rolled back.');
        }
        
        $ledger = new WalletLedger();
        $ledger->type = $service->name;
        $ledger->user_id = $lockedUser->id;
        $ledger->service = $service->name;
        $ledger->txn_id = $txn->txn_id;
        $ledger->txn_type = 'DR';
        $ledger->amount = $debit / 100;
        $ledger->opening_balance = $opening;
        $ledger->closing_balance = $lockedUser->w1 / 100;
        $ledger->narration = 'Wallet debited for ' . $service->name;
        $ledger->status = 'SUCCESS';
        $ledger->save();

        return $ledger;
    }
    
    public static function credit($user, $amount, $txn, $service){
        if($service->type == 'AMTCHG'){
            $credit = ($amount + $txn->charges) * 100;
        }elseif($service->type == 'CHG'){
            $credit = ($txn->charges) * 100;
        }elseif($service->type == 'AMTCCF'){
            $credit = ($amount + $txn->ccf) * 100;
        }else{
            $credit = $amount * 100;
        }
        $lockedUser = User::where('id', $user->id)->lockForUpdate()->first();
        $opening = $lockedUser->w1 / 100;
        
        $lockedUser->incrementW1($credit);
        $lockedUser->refresh();
        
        $ledger = new WalletLedger();
        $ledger->type = $service->name;
        $ledger->user_id = $lockedUser->id;
        $ledger->service = $service->name;
        $ledger->txn_id = $txn->txn_id;
        $ledger->txn_type = 'CR';
        $ledger->amount = $credit / 100;
        $ledger->opening_balance = $opening;
        $ledger->closing_balance = $lockedUser->w1 / 100;
        $ledger->narration = 'Wallet credited for ' . $service->name;
        $ledger->status = 'SUCCESS';
        $ledger->save();
        return $ledger;
    }
    
    public static function creditFundRequest($user, $amount, $txn){
        $lockedUser = User::where('id', $user->id)->lockForUpdate()->first();
        $opening = $lockedUser->w1 / 100;
        $credit = $amount * 100;
        
        $lockedUser->incrementW1($credit);
        $lockedUser->refresh();
        
        $ledger = new WalletLedger();
        $ledger->type = 'Fund Request';
        $ledger->user_id = $lockedUser->id;
        $ledger->service = 'Fund Request';
        $ledger->txn_id = $txn->client_ref;
        $ledger->txn_type = 'CR';
        $ledger->amount = $credit / 100;
        $ledger->opening_balance = $opening;
        $ledger->closing_balance = $lockedUser->w1 / 100;
        $ledger->narration = 'Wallet credited for Fund Request';
        $ledger->status = 'SUCCESS';
        $ledger->save();
        return $ledger;
    }
    
    public static function creditCommission($user, $amount, $txn, $service){
        $lockedUser = User::where('id', $user->id)->lockForUpdate()->first();
        $opening = $lockedUser->w1 / 100;
        if (in_array($service->id, ["11", "13", "14", "24","81","336","337"])) {
            switch ($lockedUser->role) {
                case 'ret':
                case 'dd':
                case 'api':
                    $credit = ($amount - $txn->ret_tds) * 100;
                    break;
                case 'di':
                    $credit = ($amount - $txn->di_tds) * 100;
                    break;
                case 'md':
                    $credit = ($amount - $txn->md_tds) * 100;
                    break;
                default:
                    $credit = $amount * 100;
                    break;
            }
        } else {
            $credit = $amount * 100;
        }
        
        $lockedUser->incrementW1($credit);
        $lockedUser->refresh();

        $ledger = new WalletLedger();
        $ledger->type = 'COMMISSION';
        $ledger->user_id = $lockedUser->id;
        $ledger->service = $service->name ?? 'Unknown';
        $ledger->txn_id = $txn->txn_id ?? uniqid('CM_');
        $ledger->txn_type = 'CR';
        $ledger->amount = $credit / 100;
        $ledger->opening_balance = $opening;
        $ledger->closing_balance = $lockedUser->w1 / 100;
        $ledger->narration = 'Wallet credited for ' . ($service->name ?? 'Service') . ' commission';
        $ledger->status = 'SUCCESS';
        $ledger->save();
        return $ledger;
    }

    public static function refund($user, $amount, $txn, $service){
        $lockedUser = User::where('id', $user->id)->lockForUpdate()->first();
        $opening = $lockedUser->w1 / 100;
        if($service->type == 'AMTCHG'){
            $credit = ($amount + $txn->charges) * 100;
        }elseif($service->type == 'CHG'){
            $credit = ($txn->charges) * 100;
        }elseif($service->type == 'AMTCCF'){
            $credit = ($amount + $txn->ccf) * 100;
        }else{
            $credit = $amount * 100;
        }
        
        $lockedUser->incrementW1($credit);
        $lockedUser->refresh();
        
        $ledger = new WalletLedger();
        $ledger->type = 'REFUND';
        $ledger->user_id = $lockedUser->id;
        $ledger->service = $txn->operator ?? $service->name ?? 'Unknown';
        $ledger->txn_id = $txn->txn_id ?? uniqid('RF_');
        $ledger->txn_type = 'CR';
        $ledger->amount = $credit / 100;
        $ledger->opening_balance = $opening;
        $ledger->closing_balance = $lockedUser->w1 / 100;
        $ledger->narration = 'Wallet credited for refund of failed ' . ($service->name ?? 'transaction');
        $ledger->status = 'SUCCESS';
        $ledger->save();
        return $ledger;
    }
}