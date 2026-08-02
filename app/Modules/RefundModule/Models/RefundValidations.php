<?php

namespace App\Modules\RefundModule\Models;

use Illuminate\Support\Facades\Validator;

class RefundValidations
{
    public static function refund($request)
    {
        return Validator::make($request, [
            'txn_id' => ['required', 'string'],
        ]);
    }
       public static function successTxn($request)
    {
        return Validator::make($request, [
            'txn_id' => ['required', 'string'],
            'operator_id' => ['required', 'string'],
        ]);
    }
}
