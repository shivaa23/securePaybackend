<?php

namespace App\Modules\LienModule\Models;

use Validator;
use Illuminate\Support\Facades\DB;
class LienValidation
{
     public static function validateLien($request)
    {
        $tableMap = [
            'PMT'   => 'payout_txns',
            'DMT1'  => 'dmt_txns',
            'DMT2'  => 'dmt_txns',
            'AEPS'  => 'aeps_txns',
            'AEPS2' => 'aeps_txns',
            'BBP'   => 'bbps_txns',
            'RCH'   => 'recharge_txns',
        ];
    
        return Validator::make($request, [
            'user_id'    => 'required|integer|exists:users,id',
            'give_txn_id' => [
                'sometimes',
                'string',
                'max:255',
                'unique:liens,txn_id',
                function ($attribute, $value, $fail) use ($tableMap) {
                    if (!$value) return;
    
                    $found = false;
    
                    foreach ($tableMap as $prefix => $table) {
                        if (\Illuminate\Support\Str::startsWith($value, $prefix)) {
                            if (DB::table($table)->where('txn_id', $value)->exists()) {
                                $found = true;
                            }
                            break;
                        }
                    }
    
                    if (!$found) {
                        $fail('The selected transaction ID is invalid or not found in mapped tables.');
                    }
                },
            ],
            'amount'     => 'required|numeric|min:1',
            'client_ref' => 'sometimes|string|unique:liens,client_ref|max:255',
            'remark'     => 'nullable|string|max:255',
            'mpin'       => 'required|numeric|digits:6',
        ]);
    }

   public static function removeLien($request)
   {
        return Validator::make($request, [
            'id'     => 'required|integer|exists:liens,id',         
            'remark'      => 'required|string|max:255',     
        ]);
    }

}
