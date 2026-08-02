<?php
namespace App\Modules\Aeps\Services;

use App\Modules\Aeps\Models\AepsTxn;
use Auth;

class AepsService
{
    public static function create($user, $service, $request, $comm,$amount)
    {
        $txn = new AepsTxn();
        $txn->user_id = $user->id;
        $txn->txn_id = 'AEPS' . time() . rand(100, 999);
        $txn->route = $service->route;
        $txn->client_ref = $request->client_ref;
        $txn->pf = $request->header('user-agent');
        $txn->aadhaar_number = $request->AadharNumber ?? $request->AadhaarNumber;
        $txn->iin = $request->iin ;
        $txn->bank_name = $request->bank_name ;
        $txn->operator = $service->name ;
        $txn->txn_type = "TWOFACTOR";
        $txn->amount = $amount;
        if(in_array($service->id, ["22","23"])){
            $txn->ret_comm = 0;
            $txn->charges = $comm['ret'] ?? $comm['dd'] ?? 0;
        }else{
            $txn->ret_comm = $comm['ret'] ?? $comm['dd'] ?? 0;
            $txn->charges = 0;
        }
        $txn->balance = $user->wallet_balance ?? 0;
        $txn->save();
        return $txn;
    }
     public static function createBalanceEnqueiry($user, $service, $request, $comm,$amount)
    {
        $txn = new AepsTxn();
        $txn->user_id = $user->id;
        $txn->txn_id = 'AEPS' . time() . rand(100, 999);
        $txn->route = $service->route;
        $txn->client_ref = $request->client_ref;
        $txn->pf = $request->header('user-agent');
        $txn->aadhaar_number = $request->AadharNumber ?? $request->AadhaarNumber;
        $txn->iin = $request->iin;
        $txn->bank_name = $request->bank_name;
        $txn->operator = $service->name;
        $txn->txn_type = "BALANCEENQUIRY";
        $txn->amount = $amount;
        $txn->ret_comm = $comm['ret'] ?? $comm['dd'] ;
        $txn->balance = $user->wallet_balance ;
        $txn->save();
        return $txn;
    }
    public static function createBalanceEnqueiry2($user, $service, $request, $comm,$amount)
    {
        $txn = new AepsTxn();
        $txn->user_id = $user->id;
        $txn->txn_id = 'AEPS2' . time() . rand(100, 999);
        $txn->route = $service->route;
        $txn->client_ref = $request->client_ref;
        $txn->pf = $request->header('user-agent');
        $txn->aadhaar_number = $request->AadharNumber ?? $request->AadhaarNumber;
        $txn->iin = $request->iin;
        $txn->bank_name = $request->bank_name;
        $txn->operator = $service->name;
        $txn->txn_type = "BALANCEENQUIRY";
        $txn->amount = $amount;
        $txn->ret_comm = $comm['ret'] ?? $comm['dd'] ;
        $txn->balance = $user->wallet_balance ;
        $txn->save();
        return $txn;
    }
    public static function createMiniStatement($user, $service, $request, $comm,$amount)
    {
        $txn = new AepsTxn();
        $txn->user_id = $user->id;
        $txn->txn_id = 'AEPS' . time() . rand(100, 999);
        $txn->route = $service->route;
        $txn->client_ref = $request->client_ref;
        $txn->pf = $request->header('user-agent');
        $txn->aadhaar_number = $request->AadharNumber ?? $request->AadhaarNumber;
        $txn->iin = $request->iin;
        $txn->bank_name = $request->bank_name ;
        $txn->operator = $service->name ;
        $txn->txn_type = "MINISTATEMENT";
        $txn->amount = $amount;
        $txn->ret_comm = $comm['ret'] ?? $comm['dd'];
        $txn->balance = $user->wallet_balance ;
        $txn->save();
        return $txn;
    }
    public static function createMiniStatement2($user, $service, $request, $comm,$amount)
    {
        $txn = new AepsTxn();
        $txn->user_id = $user->id;
        $txn->txn_id = 'AEPS2' . time() . rand(100, 999);
        $txn->route = $service->route;
        $txn->client_ref = $request->client_ref;
        $txn->pf = $request->header('user-agent');
        $txn->aadhaar_number = $request->AadharNumber ?? $request->AadhaarNumber;
        $txn->iin = $request->iin;
        $txn->bank_name = $request->bank_name ;
        $txn->operator = $service->name ;
        $txn->txn_type = "MINISTATEMENT";
        $txn->amount = $amount;
        $txn->ret_comm = $comm['ret'] ?? $comm['dd'];
        $txn->balance = $user->wallet_balance ;
        $txn->save();
        return $txn;
    }

     public static function createCashWithdraw($user, $service, $request, $comm,$a_comm)
    {
        $txn = new AepsTxn();
        $txn->user_id = $user->id;
        $txn->txn_id = 'AEPS' . time() . rand(100, 999);
        $txn->route = $service->route;
        $txn->client_ref = $request->client_ref;
        $txn->pf = $request->header('user-agent');
        $txn->aadhaar_number = $request->AadharNumber ?? $request->AadhaarNumber;
        $txn->iin = $request->bank_iin ;
        $txn->bank_name = $request->BankName;
        $txn->operator = $service->name ;
        $txn->txn_type = "CASH WITHDRAWAL";
        $txn->amount = $request->amount;
        $txn->ret_comm = $comm['ret'] ?? $comm['dd'] ;
        $txn->di_comm = $comm['di']??0 ;
        $txn->md_comm = $comm['md']??0 ;
        $txn->ret_tds = $txn->ret_comm * 2 / 100;
        $txn->di_tds  = $txn->di_comm * 2 / 100;
        $txn->md_tds  = $txn->md_comm * 2 / 100 ;
        $txn->a_comm = $a_comm- $txn->ret_comm-$txn->di_comm-$txn->md_comm;
        $txn->balance = $user->wallet_balance ;
        $txn->save();
        return $txn;
    }
    
     public static function createCashWithdraw2($user, $service, $request, $comm,$a_comm)
    {
        $txn = new AepsTxn();
        $txn->user_id = $user->id;
        $txn->txn_id = 'AEPS2' . time() . rand(100, 999);
        $txn->route = $service->route;
        $txn->client_ref = $request->client_ref;
        $txn->pf = $request->header('user-agent');
        $txn->aadhaar_number = $request->AadharNumber ?? $request->AadhaarNumber;
        $txn->iin = $request->bank_iin ;
        $txn->bank_name = $request->BankName;
        $txn->operator = $service->name ;
        $txn->txn_type = "CASH WITHDRAWAL";
        $txn->amount = $request->amount;
        $txn->ret_comm = $comm['ret'] ?? $comm['dd'] ;
        $txn->di_comm = $comm['di']??0 ;
        $txn->md_comm = $comm['md']??0 ;
        $txn->ret_tds = $txn->ret_comm * 2 / 100;
        $txn->di_tds  = $txn->di_comm * 2 / 100;
        $txn->md_tds  = $txn->md_comm * 2 / 100 ;
        $txn->a_comm = $a_comm- $txn->ret_comm-$txn->di_comm-$txn->md_comm;
        $txn->balance = $user->wallet_balance ;
        $txn->save();
        return $txn;
    }

}
