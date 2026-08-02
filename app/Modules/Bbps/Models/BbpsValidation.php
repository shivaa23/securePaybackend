<?php
namespace App\Modules\Bbps\Models;

use Illuminate\Database\Eloquent\Model;
use Validator;

class BbpsValidation extends Model
{
    protected $fillable = [];

    /**
     * Validate Pay Bill Request
     */
    public static function payBill($request)
    {
        return Validator::make(
            $request,
            [
                'billerId'            => 'required',
                'biller_name'         => 'required',
                'param1'              => 'required',
                'param2'              => 'sometimes',
                'enquiryReferenceId'  => 'required',
                'amount'              => 'required|numeric|between:10,500000',
                'cat'                 => 'required',
                'mpin'                => 'required|digits:6',
                'latitude'            => 'required',
                'longitude'           => 'required',
                 "client_ref"   => 'required|string|unique:bbps_txns,client_ref',
            ]
        );
    }
}
