<?php

namespace App\Modules\Complains\Models;

use Illuminate\Support\Facades\Validator;

class ComplainsValidation
{
    public static function create($data)
    {
        return Validator::make($data, [
            'txn_id'  => 'required|string',
            'message' => 'required|string',
            'type'    => 'required|string|in:dmt,recharge,payout,aeps,bbps'
        ]);
    }

    public static function update($data)
    {
        return Validator::make($data, [
            'id'     => 'required|integer|exists:complains,id',
            'remark' => 'nullable|string|max:500',
            'status' => 'required|string|in:open,resolved,rejected',
        ]);
    }
}
