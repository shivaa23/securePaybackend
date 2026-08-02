<?php
namespace App\Modules\Payout\Models;

use Illuminate\Database\Eloquent\Model;
use App\Modules\Integration\Models\Ipay;
use App\Modules\Dmt\Models\DmtBank;
use App\Modules\Integration\Models\Impsguru;
use Validator;
use Auth;

class BeneficiaryValidation extends Model
{
        public static function createBeneficiary($request)
        {
            return Validator::make($request, [
                'sender_id'         => 'required|integer|exists:payout_senders,id',
                'beneficiary_name'  => 'required|string|min:3|max:50',
                'account_number'    => 'required|string',
                'ifsc_code'         => 'required_unless:type,UPI|string|regex:/^[A-Z]{4}0[A-Z0-9]{6}$/',
                'bank_name'         => 'required_unless:type,UPI|string|min:3|max:100',
                'mobile_number'     => 'sometimes|numeric|regex:/^[6-9][0-9]{9}$/u',
                'type'              => 'required|in:UPI,BANK',
            ], [
                'ifsc_code.required_unless' => 'IFSC code is required unless type is UPI.',
                'type.required'             => 'Type is required.',
                'type.in'                   => 'Type must be either UPI or BANK.',
            ]);
        }

    public static function getBeneficiarySchema($request)
    {
        // Fetch banks with pipe = INSTANTPAY
$response = DmtBank::where('pipe', 'INSTANTPAY')
    ->orderBy('name', 'asc')
    ->get();
    
    $banks = $response
        ->unique('name')   
        ->values() 
        ->map(function ($bank) {
            return [
                'bank_name' => $bank->name,
                'bank_id'   => $bank->bank_id,
                'ifsc'      => $bank->ifsc ?? null
            ];
        })
        ->toArray();


        return [
            "formName" => "Add Beneficiary",
            "fields" => [
                [
                    "name" => "bank_id",
                    "label" => "Bank",
                    "type" => "autocomplete",
                    "placeholder" => "Select a bank",
                    "options" => $banks,
                    "required" => true,
                    "validation" => ["type" => "string"]
                ],
                [
                    "name" => "ifsc_code",
                    "label" => "IFSC Code",
                    "type" => "text",
                    "placeholder" => "Enter IFSC code",
                    "required" => true,
                    "validation" => [
                        "type" => "string",
                        "regex" => "/^[A-Z]{4}0[A-Z0-9]{6}$/"
                    ]
                ],
                [
                    "name" => "beneficiary_name",
                    "label" => "Beneficiary Name",
                    "type" => "text",
                    "placeholder" => "Enter beneficiary name",
                    "required" => true,
                    "validation" => ["type" => "string"]
                ],
                
                [
                    "name" => "account_number",
                    "label" => "Account Number",
                    "type" => "text",
                    "placeholder" => "Enter account number",
                    "required" => true,
                    "validation" => ["type" => "string"]
                ],
                
                // [
                //     "name" => "mobile_number",
                //     "label" => "Mobile Number",
                //     "type" => "text",
                //     "placeholder" => "Enter mobile number",
                //     "required" => true,
                //     "validation" => [
                //         "type" => "numeric",
                //         "regex" => "/^[6-9][0-9]{9}$/"
                //     ]
                // ]
            ]
        ];
    }


    public static function getBeneficiarySchemaUpi()
    {
        return [
            "formName" => "Beneficiary Schema",
            "fields" => [
                 [
                    "name" => "beneficiary_name",
                    "label" => "Beneficiary Name",
                    "type" => "text",
                    "placeholder" => "Enter beneficiary name",
                    "required" => true,
                    "validation" => ["type" => "string", "minLength" => 3, "maxLength" => 50, ]
                ],
                [
                    "name" => "prefix",
                    "label" => "Prefix",
                    "type" => "text",
                    "placeholder" => "Prefix",
                    "required" => true,
                    "validation" => [
                        "type" => "string",
                       
                    ]
                ],
                [
                    "name" => "suffix",
                    "label" => "Suffix",
                    "type" => "dropdown",
                    "placeholder" => "Select payment method",
                    "required" => true,
                    "options" => [
                        ["label" => "Paytm", "value" => "paytm"],
                        ["label" => "PhonePe", "value" => "ybl"],
                        ["label" => "Google Pay (okhdfcbank)", "value" => "okhdfcbank"],
                        ["label" => "Google Pay (okicici)", "value" => "okicici"],
                        ["label" => "Google Pay (oksbi)", "value" => "oksbi"],
                        ["label" => "Amazon Pay", "value" => "apl"],
                        ["label" => "BHIM", "value" => "upi"],
                        ["label" => "ICICI Bank", "value" => "icici"],
                        ["label" => "HDFC Bank", "value" => "hdfcbank"],
                        ["label" => "Axis Bank", "value" => "axisbank"],
                        ["label" => "SBI (State Bank of India)", "value" => "sbi"],
                        ["label" => "Kotak Mahindra Bank", "value" => "kotak"],
                        ["label" => "Punjab National Bank (PNB)", "value" => "pnb"],
                        ["label" => "Bank of Baroda", "value" => "barodampay"],
                        ["label" => "Yes Bank", "value" => "yesbank"],
                        ["label" => "Other", "value" => "Other"],
                    ]
                ]
            ]
        ];
    }



    public static function updateBeneficiary($request)
    {
        return Validator::make($request, [
            'id'                => 'required|integer|exists:payout_beneficiaries,id',
            'sender_id'         => 'sometimes|integer|exists:senders,id',
            'beneficiary_name'  => 'sometimes|string|min:3|max:50|regex:/^[a-zA-Z\s]+$/u',
            'account_number'    => 'sometimes|string|regex:/^[0-9]{6,18}$/',
            'ifsc_code'         => 'sometimes|string|regex:/^[A-Z]{4}0[A-Z0-9]{6}$/',
            'bank_name'         => 'sometimes|string|min:3|max:100',
            'mobile_number'     => 'sometimes|numeric|regex:/^[6-9][0-9]{9}$/u',
            'is_verified'       => 'sometimes|boolean',
            'is_active'         => 'sometimes|boolean',
        ], [
            'id.required'              => 'Beneficiary ID is required.',
            'id.integer'               => 'Beneficiary ID must be a valid number.',
            'id.exists'                => 'Beneficiary does not exist.',

            'sender_id.integer'        => 'Sender ID must be a valid number.',
            'sender_id.exists'         => 'Sender ID does not exist.',

            'beneficiary_name.string'  => 'Beneficiary name must be a string.',
            'beneficiary_name.regex'   => 'Beneficiary name must not contain numbers or special characters.',
            'beneficiary_name.min'     => 'Beneficiary name must be at least 3 characters.',
            'beneficiary_name.max'     => 'Beneficiary name must not exceed 50 characters.',

            'account_number.string'    => 'Account number must be a string.',
            'account_number.regex'     => 'Account number must be numeric and between 6 to 18 digits.',

            'ifsc_code.string'         => 'IFSC code must be a string.',
            'ifsc_code.regex'          => 'Invalid IFSC code format.',

            'bank_name.string'         => 'Bank name must be a string.',
            'bank_name.min'            => 'Bank name must be at least 3 characters.',
            'bank_name.max'            => 'Bank name must not exceed 100 characters.',

            'mobile_number.numeric'    => 'Mobile number must be numeric.',
            'mobile_number.regex'      => 'Please enter a valid 10-digit Indian mobile number.',
        ]);
    }

 
    public static function deleteBeneficiary($request)
    {
        return Validator::make($request, [
            'id' => 'required|integer|exists:payout_beneficiaries,id',
        ], [
            'id.required' => 'Beneficiary ID is required.',
            'id.integer'  => 'Beneficiary ID must be a valid number.',
            'id.exists'   => 'Beneficiary does not exist.',
        ]);
    }
}
