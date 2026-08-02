<?php
namespace App\Modules\Payout\Models;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Validation\Rule;
use Validator;
use Auth;

class PayoutValidation extends Model
{
    
    public static function payOut($request){
        $user = Auth::user();

        $operatorRule = Rule::exists('services', 'id')
            ->where(function ($query) use ($user) {
                // Always check global is_active
                $query->where('is_active', 1);
        
                // Role-based check
                if ($user->role === 'api') {
                    $query->where('is_active_api', 1);
                } else {
                    $query->where('is_active_users', 1);
                }
            });
            
        return Validator::make($request, [
            'client_ref'     => 'required|unique:payout_txns|string|min:10|max:20',
            'beneficiary_name'     => 'required|string|min:3|max:255',
            'purpose'     => 'sometimes|string',
            'bank_name' => $request['operator'] == 12 ? 'nullable|string|min:3|max:50|regex:/^[a-zA-Z\s]+$/u' : 'required|string|min:3|max:60',
            'account_number' => $request['operator'] == 12 ? 'required|string|regex:/^[A-Za-z0-9._-]{2,50}@[A-Za-z0-9]{2,64}$/': 'required|string|regex:/^[A-Za-z0-9]{6,20}$/',
            'ifsc_code' => $request['operator'] == 12 ? 'nullable|string|regex:/^[A-Z]{4}0[A-Z0-9]{6}$/' : 'required|string|regex:/^[A-Z]{4}0[A-Z0-9]{6}$/',
            'mop' => $request['operator'] == 12 ? 'required|string|in:UPI' : 'required|string|in:NEFT,IMPS,RTGS',
            'latitude'  => 'required|numeric|between:-90,90',
            'longitude' => 'required|numeric|between:-180,180',
            'amount' => $request['operator'] == 12 ? 'required|numeric|min:1000|max:25000' : 'required|numeric|min:100|max:49000',
            'mobile_number'  => 'required|numeric|regex:/^[6-9][0-9]{9}$/u',
            'operator' => ['required','integer','in:11,12',$operatorRule],
            // 'otp'      => 'required|digits:6',
            // 'otp_ref'  => 'required|digits_between:6,12|exists:otps,otp_ref',

        ],
        [
            'client_ref.required' => 'client_ref is required.',
            'client_ref.string'   => 'The client_ref must be a string.',
            'client_ref.unique'   => 'The client_ref must be a unique.',
            'client_ref.min'      => 'The client_ref should be minimum 10 digits.',
            'client_ref.max'      => 'The client_ref should be maximum 20 digits.',
            'client_ref.regex'   => 'The client_ref must not contain numbers and special characters.',
            
            'beneficiary_name.required' => 'beneficiary_name is required.',
            'beneficiary_name.string'   => 'The beneficiary_name must be a string.',
            'otp.required' => 'OTP is required.',
            'otp.digits'   => 'OTP must be exactly 6 digits.',
            'otp_ref.exists'   => 'Invalid OTP reference.',
            'otp_ref.required' => 'OTP reference is required.',
            'otp_ref.integer'  => 'OTP reference must be a number.',
            'otp_ref.digits_between' => 'OTP reference must be between 6 and 12 digits.',
            'bank_name.required' => 'bank_name is required.',
            'bank_name.string'   => 'The bank_name must be a string.',
            'bank_name.min'      => 'The bank_name should be minimum 3 digits.',
            'bank_name.max'      => 'The bank_name should be maximum 50 digits.',
            'bank_name.regex'   => 'The bank_name must be numeric or alphanumeric string having 6 to 50 digits.',
            
            'account_number.required' => 'account_number is required.',
            'account_number.string' => 'account_number must be a string.',
            'account_number.regex'   => 'The account_number must be numeric or alphanumeric string having 3 to 50 digits.',
            
            'ifsc_code.required' => 'ifsc_code is required.',
            'ifsc_code.string' => 'ifsc_code must be a string.',
            'ifsc_code.regex'   => 'The ifsc_code must be a alphanumeric string having 11 digits.',
            
            'mop.required' => 'mop is required.',
            'mop.in'       => 'mop must be one of: UPI for UPI Transfer else NEFT, IMPS, RTGS.',
            
            'latitude.required'  => 'Latitude is required.',
            'latitude.numeric'   => 'Latitude must be a number.',
            'latitude.between'   => 'Latitude must be between -90 and 90.',
            'longitude.required' => 'Longitude is required.',
            'longitude.numeric'  => 'Longitude must be a number.',
            'longitude.between'  => 'Longitude must be between -180 and 180.',
            
            'amount.required' => 'Amount is required.',
            'amount.numeric'  => 'Amount must be a valid number.',
            'amount.min'      => $request['operator'] == 12 ? 'Minimum amount allowed is 1000.' : 'Minimum amount allowed is 10.',
            'amount.max'      => $request['operator'] == 12 ? 'Maximum amount allowed is 25000.' : 'Minimum amount allowed is 49000.',
        
            'mobile_number.required' => 'Mobile number is required.',
            'mobile_number.numeric'  => 'The mobile number must be a number.',
            'mobile_number.regex'    => 'Please enter a valid 10-digit Indian mobile number.',
        
            'operator.required' => 'Operator field is required.',
            'operator.integer'  => 'Operator must be a valid number.',
            'operator.between'  => 'Operator must be between 1 and 500.',
            'operator.exists'   => 'Selected operator does not exist or is inactive.',
            'operator.in'   => 'Selected operator is wrong.',
        ]);
	}
    public static function upiVerify($request)
    {
        $user = Auth::user();
    
        $operatorRule = Rule::exists('services', 'id')
            ->where(function ($query) use ($user) {
                $query->where('is_active', 1);
                if ($user->role === 'api') {
                    $query->where('is_active_api', 1);
                } else {
                    $query->where('is_active_users', 1);
                }
            });
    
        return Validator::make($request, [
            'account_number'       => 'required|string',
            'mobile_number'    => 'required|digits:10',
            'latitude'  => 'required|numeric|between:-90,90',
            'longitude' => 'required|numeric|between:-180,180',
            'mpin'      => 'required|numeric|digits:6',
            'operator'      => ['required', 'integer', 'in:21', $operatorRule],
        ], [
            'vpa.required'       => 'VPA is required.',
            'number.required'    => 'Remitter number is required.',
            'latitude.required'  => 'Latitude is required.',
            'longitude.required' => 'Longitude is required.',
            'mpin.required'      => 'MPIN is required.',
            'mpin.digits'        => 'MPIN must be exactly 6 digits.',
            'mobile_number.required' => 'Mobile number is required.',
            'mobile_number.regex'    => 'Please enter a valid 10-digit Indian mobile number.',
            'operator.required'      => 'Operator field is required.',
            'operator.in'            => 'Selected operator is invalid.',
            'operator.exists'        => 'Selected operator does not exist or is inactive.',
        ]);
    }

	public static function getPayoutSchema()
    {
        return [
            "formName" => "Payout Schema",
            "fields" => [
                [
                    "name" => "mop",
                    "label" => "Mode of Payment",
                    "type" => "select",
                    "placeholder" => "Select mode of payment",
                    "required" => true,
                    "options" => ["UPI", "NEFT", "IMPS", "RTGS"],
                    "validation" => [
                        "type" => "string",
                        "enum" => ["UPI", "NEFT", "IMPS", "RTGS"]
                    ]
                ],
                [
                    "name" => "amount",
                    "label" => "Amount",
                    "type" => "number",
                    "placeholder" => "Enter amount",
                    "required" => true,
                    "validation" => [
                        "type" => "numeric",
                        "min" => 10,
                        "max" => 100000
                    ]
                ],
           
            ]
        ];
    }
    public static function otpExp($request)
    {
        return Validator::make($request, [
            'mobile_number' => 'required|regex:/^[6-9][0-9]{9}$/',
            'amount'        => 'required|numeric|min:1', // integer > 1
        ], [
            'mobile_number.required' => 'Mobile number is required.',
            'mobile_number.regex'    => 'Please enter a valid 10-digit Indian mobile number.',
            'amount.required'        => 'Amount is required.',
            'amount.numeric'         => 'Amount must be a number.',
            'amount.min'             => 'Amount must be greater than 0.',
        ]);
    }
      public static function createPurpose($request)
    {
        return Validator::make($request, [
            'type'        => 'required|string',
            'description' => 'sometimes|string|min:2', // ensures at least 2 characters
        ]);
    }
	
	public static function updatePayoutSender($request){
        return Validator::make($request, [
            'id' => 'required|integer',
       
        ]);
	}


}