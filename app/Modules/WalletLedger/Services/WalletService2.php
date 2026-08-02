<?php
namespace App\Modules\WalletLedger\Services;
use App\Modules\WalletLedger\Models\WalletLedger2;
class WalletService2
{
    public static function credit($user, $amount, $txn, $service)
    {
        $opening = $user->w2 / 100;
        if(in_array($service->id, [50,51,53,54])){
            $credit = $amount * 100;
        }else{
            $credit = $amount * 100;
        }
        $user->incrementW2($credit);
        
        $ledger = new WalletLedger2();
        $ledger->type = $service->name;
        $ledger->user_id = $user->id;
        $ledger->service = $service->name;
        $ledger->txn_id = $txn->txn_id;
        $ledger->txn_type = 'CR';
        $ledger->amount = $credit/100;
        $ledger->opening_balance = $opening;
        $ledger->closing_balance = $user->w2/100;
        $ledger->narration = 'Wallet creditted for '.$service->name;
        $ledger->status = 'SUCCESS';
        $ledger->save();
        return $ledger;
    }
      public static function debit($user, $amount, $txn, $service)
    {
        $opening = $user->w2 / 100;
        if(in_array($service->id, [50,51,53,54])){
            $credit = $amount * 100;
        }else{
            $credit = $amount * 100;
        }
        $user->decrementW2($credit);
        
        $ledger = new WalletLedger2();
        $ledger->type = $service->name;
        $ledger->user_id = $user->id;
        $ledger->service = $service->name;
        $ledger->txn_id = $txn->txn_id;
        $ledger->txn_type = 'DR';
        $ledger->amount = $credit/100;
        $ledger->opening_balance = $opening;
        $ledger->closing_balance = $user->w2/100;
        $ledger->narration = 'Wallet creditted for '.$service->name;
        $ledger->status = 'SUCCESS';
        $ledger->save();
        return $ledger;
    }

    public static function creditCommission($user, $txn, $service)
    {
        $opening = $user->w2 / 100;
        if(in_array($service->id, [50,51,53,54])){
            if($user->role === 'ret' || $user->role === 'dd' || $user->role === 'api'){
                $credit = ($txn->ret_comm - $txn->ret_tds) * 100;
            }elseif($user->role === 'di'){
                $credit = ($txn->di_comm - $txn->di_tds) * 100;
            }elseif($user->role === 'md'){
                $credit = ($txn->md_comm - $txn->md_tds) * 100;
            }else{
                $credit = ($txn->ret_comm) * 100;
            }
        }else{
            $credit = $txn->ret_comm * 100;
        }
        $user->incrementW2($credit);

        $ledger = new WalletLedger2();
        $ledger->type = $service->name;
        $ledger->user_id = $user->id;
        $ledger->service = $service->name;
        $ledger->txn_id = $txn->txn_id;
        $ledger->txn_type = 'CR';
        $ledger->amount = $credit/100;
        $ledger->opening_balance = $opening;
        $ledger->closing_balance = $user->w2/100;
        $ledger->narration = 'Wallet credited for Commission '.$service->name;
        $ledger->status = 'SUCCESS';
        $ledger->save();
    }
    public static function claim($user, $collections, $service)
    {
        $opening = $user->w2 / 100;
        // if(in_array($service->id, [50,51,53,54])){
        //     if($user->role === 'ret' || $user->role === 'dd' || $user->role === 'api'){
        //         $credit = ($txn->ret_comm - $txn->ret_tds) * 100;
        //     }elseif($user->role === 'di'){
        //         $credit = ($txn->di_comm - $txn->di_tds) * 100;
        //     }elseif($user->role === 'md'){
        //         $credit = ($txn->md_comm - $txn->md_tds) * 100;
        //     }else{
        //         $credit = ($txn->ret_comm) * 100;
        //     }
        // }else{
        //     $credit = $txn->ret_comm * 100;
        // }
        $user->incrementW2($collections->amount*100);

        $ledger = new WalletLedger2();
        $ledger->type = $service->name;
        $ledger->user_id = $user->id;
        $ledger->service = $service->name;
        $ledger->txn_id = $collections->refId ;
        $ledger->txn_type = 'CR';
        $ledger->amount = $collections->amount;
        $ledger->opening_balance = $opening;
        $ledger->closing_balance = $user->w2/100;
        $ledger->narration = 'Collection Commission '.$service->name;
        $ledger->status = 'SUCCESS';
        $ledger->save();
    }
    public static function claim2($user, $collections, $service)
    {
        $opening = $user->w2 / 100;
    
        $charges = $collections->charges ?? 0;
    
        $totalAmount = ($collections->amount - $charges);
    
        // Convert rupees to paise before increment
        $user->incrementW2($totalAmount * 100);
    
        $ledger = new WalletLedger2();
        $ledger->type = $service->name;
        $ledger->user_id = $user->id;
        $ledger->service = $service->name;
        $ledger->txn_id = $collections->txn_id;
        $ledger->txn_type = 'CR';
        $ledger->amount = $totalAmount;
        $ledger->opening_balance = $opening;
        $ledger->closing_balance = $user->fresh()->w2 / 100; // safer
        $ledger->narration = 'Collection Amount After Charges - ' . $service->name;
        $ledger->status = 'SUCCESS';
        $ledger->save();
    }



    // public static function refund($user, $amount, $txn, $service)
    // {
    //     $opening = $user->w2 / 100;
    //     if(in_array($service->id, ["11","12","24"])){
    //         $debit = ($amount + $txn->charges) * 100;
    //     }elseif(in_array($service->id, ["13","14"])){
    //         $debit = ($amount + $txn->ccf) * 100;
    //     }else{
    //         $debit = $amount * 100;
    //     }
    //     $user->incrementW1($debit);
        
    //     $ledger = new WalletLedger2();
    //     $ledger->type = 'refund';
    //     $ledger->user_id = $user->id;
    //     $ledger->service = $txn->operator;
    //     $ledger->txn_id = $txn->txn_id;
    //     $ledger->txn_type = 'CR';
    //     $ledger->amount = $debit/100;
    //     $ledger->opening_balance = $opening;
    //     $ledger->closing_balance = $user->w1/100;
    //     $ledger->narration = 'Wallet refund for failed '.$service->name;
    //     $ledger->status = 'SUCCESS';
    //     $ledger->save();
    // }
}