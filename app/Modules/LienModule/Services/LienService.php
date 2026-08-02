<?php
namespace App\Modules\LienModule\Services;

use App\Modules\LienModule\Models\Lien;
use App\Modules\User\Models\User;
use Exception;

class LienService
{
    public static function create($user, $request,$result)
    {
        $targetUser = User::find($request->user_id);

        if (!$targetUser) {
            throw new Exception("Target user not found");
        }
        $lien = new Lien();
        $lien->user_id    = $targetUser->id;                   
        $lien->marked_by  = $user->name; 
        $lien->txn_id = 'LIEN' . time() . rand(100, 999); 
        $lien->given_txn_id =$request->given_txn_id; 
        $lien->client_ref = $request->client_ref;       
        $lien->amount     = $request->amount;   
        $lien->remark     = $request->given_txn_id?$result->operator:$request->remark;           
        if (!$lien->save()) {
            throw new Exception("Failed to save lien record");
        }

        // Increment lien balance after successful save
        $targetUser->incrementLien($request->amount);

        return $lien;
    }
      public static function removeLien($user, $request,$lien)
    {
        $lien->remark     = $request->remark;  
         $lien->marked_by  = $user->name;  
        $lien->status     = "removed";   
        $targetUser = User::find($lien->user_id);

        if (!$lien->save()) {
            throw new Exception("Failed to save lien record");
        }
        $targetUser->decrementLien($lien->amount);

        return $lien;
    }
}
