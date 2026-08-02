<?php

namespace App\Modules\Ipay\Models;

use Illuminate\Database\Eloquent\Model;
use Validator;

class IpayValidation extends Model
{
    protected $fillable = [];


public static function updateIpay($data)
{
    return Validator::make($data, [
        'id'        => 'required|exists:ipay_details,id',
        'instid'    => 'sometimes|string|max:100',
        'ipay_id'=>'sometimes|string|max:100',
        'latitude'  => 'sometimes|numeric',
        'longitude' => 'sometimes|numeric',
    ]);
}

  
     public static function deleteIpay(array $request)
    {
        return Validator::make($request, [
            'id' => 'required|integer|exists:ipay,id',
        ]);
    }
    

}
