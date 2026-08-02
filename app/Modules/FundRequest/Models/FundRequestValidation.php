<?php

namespace App\Modules\FundRequest\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Validator;
use App\Modules\Utils\getModes;
use App\Modules\Bank\Models\Bank;
use App\Modules\FundRequest\Models\FundTxnId;
use Auth;

class FundRequestValidation extends Model
{
    public static function createFundRequest($request)
    {
        return Validator::make($request, [
            'bank_name'   => 'required|string',
            'mode'        => 'required|string',
            'bank_ref_id' => [
                'required',
                'string',
            ],
            'date'        => 'required|date',
            'amount'      => 'required|numeric',
            'remark'      => 'required|string',
            'client_ref'  => [
                'required',
                'string',
                'min:1',
                'unique:fund_requests,client_ref',
            ],
          'receipt' => 'sometimes|image|mimes:jpg,jpeg,png|max:2048',
    
        ]);
    }
  public static function createFundRequest1($request)
{
    $rules = [
        'bank_name'   => 'required',
        'mode'        => 'required',
        'bank_ref_id' => 'required',
        'date'        => 'required',
        'amount'      => 'required',
        'remark'      => 'required',
        'client_ref'  => 'required',
    ];

    // 🔁 Dynamically validate receipt chunks
    foreach ($request as $key => $value) {
        if (preg_match('/^receipt\d+$/', $key)) {
            $rules[$key] = 'sometimes|string';
        }
    }

    return Validator::make($request, $rules);
}
    
    public static function updateFundRequest($request)
    {
        return Validator::make($request, [
            'id'     => 'required|exists:fund_requests,id',
            'status' => 'required|in:approved,rejected',
             'admin_remark' => 'sometimes',
            
        ]);
    }
    public static function editFundRequest($request)
    {
        return Validator::make($request, [
            'id'          => 'required|exists:fund_requests,id',
            'bank_name'   => 'sometimes|string|max:255',
            'receipt' => 'sometimes|image|mimes:jpg,jpeg,png|max:2048',
            'mode'        => 'sometimes|string|max:50',
            'bank_ref_id' => 'sometimes|string|max:100',
            'date'        => 'sometimes|date',
            'remark'      => 'sometimes|string|max:500',
            // 'amount'   => 'sometimes|numeric|min:1', // uncomment if you allow updating amount
            // 'mpin'        => 'required|string|min:4|max:6' // if MPIN is required for edit
        ]);
    }
    
    public static function getSchema(){
        require_once base_path('app/Modules/Utils/getModes.php'); 
    
     
        $modes = getModes();
        $banks = Bank::where("status", 1)->get();
        // $txn = "TXN" . time() . rand(1000, 9999);
        $user = Auth::user(); 
        // $txnId = new FundTxnId();
        // $txnId->user_id = $user->id;
        // $txnId->txn_id = $txn;
        // $txnId->save();
    
        return [
            "formName" => "Fund Request",
            "fields" => [
           
                [
                    "name" => "bank_name",
                    "label" => "Bank Name",
                    "type" => "dropdown",
                    "placeholder" => "Enter bank name",
                    "required" => true,
                    "options" => $banks, 
                    "validation" => ["type" => "string"]
                ],
                [
                    "name" => "mode",
                    "label" => "Mode",
                    "type" => "dropdown",
                    "placeholder" => "Select mode",
                    "required" => true,
                    "options" => $modes, 
                    "validation" => ["type" => "string"]
                ],
                [
                    "name" => "bank_ref_id",
                    "label" => "Bank Reference ID ",
                    "type" => "text",
                    "placeholder" => "Enter reference id",
                    "required" => true,
                    "validation" => ["type" => "string"]
                ],
                [
                    "name" => "date",
                    "label" => "Transaction Date",
                    "type" => "number",
                    "placeholder" => "Enter date as timestamp",
                    "required" => true,
                    "validation" => ["type" => "date"]
                ],
                [
                    "name" => "amount",
                    "label" => "Amount",
                    "type" => "number",
                    "placeholder" => "Enter amount",
                    "required" => true,
                    "validation" => ["type" => "numeric", "min" => 0]
                ],
                [
                    "name" => "remark",
                    "label" => "Remark",
                    "type" => "textarea",
                    "placeholder" => "Enter remark",
                    "required" => true,
                    "validation" => ["type" => "string"]
                ],
                [
                    "name" => "receipt",
                    "label" => "Upload receipt",
                    "type" => "file",
                    "required" => false,
                    "validation" => ["type" => "string", "minLength" => 1]
                ]
            ]
        ];
    }
    public static function deleteFundRequest($request){
        return Validator::make($request, [
            'id'   => 'required|exists:fund_requests,id',
            'mpin' => 'required|string|min:4|max:6'
        ]);
    }


}
