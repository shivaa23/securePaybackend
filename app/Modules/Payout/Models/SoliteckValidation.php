<?php
namespace App\Modules\Payout\Models;
use Illuminate\Database\Eloquent\Model;
use Validator;
use Auth;
use Illuminate\Validation\Rule;
class SoliteckValidation extends Model
{
   public static function registerRemitter($request){
        $user = Auth::user();
    
        return Validator::make($request, [
            'db_status'      => 'required|in:0,1',
            'vendor_id'      => 'required|integer',
            'otp'            => 'required|string',
            'fname'          => 'required_if:db_status,0|string',
            'lname'          => 'required_if:db_status,0|string',
            'pincode'        => 'required_if:db_status,0|digits:6',
            'mobile_number'  => 'required|numeric|regex:/^[6-9][0-9]{9}$/u',
            // 'email'       => 'required|email|unique:payout_senders,email',
        ], [
            'db_status.required' => 'DB status is required.',
            'db_status.in'       => 'DB status must be 0 or 1.',
            'vendor_id.required' => 'Vendor ID is required.',
            'vendor_id.integer'  => 'Vendor ID must be an integer.',
            'otp.required'       => 'OTP is required.',
            'fname.required_if'  => 'First name is required when DB status is 0.',
            'lname.required_if'  => 'Last name is required when DB status is 0.',
            'pincode.required_if'=> 'Pincode is required when DB status is 0.',
            'pincode.digits'     => 'Pincode must be exactly 6 digits.',
            'mobile_number.required' => 'Mobile number is required.',
            'mobile_number.numeric'  => 'Mobile number must be numeric.',
            'mobile_number.regex'    => 'Enter a valid 10-digit Indian mobile number.',
            'mobile_number.unique'   => 'This mobile number is already registered.',
            // 'email.required'       => 'Email is required.',
            // 'email.email'          => 'Enter a valid email address.',
            // 'email.unique'         => 'This email is already registered.',
        ]);
    }
   public static function payOut2($request){
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
             'operator' => ['required','integer','in:81',$operatorRule],
            'bank_name' => $request['operator'] == 12 ? 'nullable|string|min:3|max:50' : 'required|string|min:3|max:50',
            'account_number' => $request['operator'] == 12 ? 'required|string|regex:/^[a-zA-Z0-9.\-_]{2,50}@[a-zA-Z]{3,64}$/' : 'required|string|regex:/^[A-Z0-9]{6,20}$/',
            'ifsc_code' => $request['operator'] == 12 ? 'nullable|string|regex:/^[A-Z]{4}0[A-Z0-9]{6}$/' : 'required|string|regex:/^[A-Z]{4}0[A-Z0-9]{6}$/',
            'mop' => $request['operator'] == 12 ? 'required|string|in:UPI' : 'required|string|in:NEFT,IMPS,RTGS',
            'latitude'  => 'required|numeric|between:-90,90',
            'longitude' => 'required|numeric|between:-180,180',
            'amount' => 'required|numeric|min:10|max:50000',
            'mobile_number'  => 'required|numeric|regex:/^[6-9][0-9]{9}$/u',
          
            'soliteck_id'      => 'required',
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
            'bank_name.regex'   => 'The bank_name must be numeric or alphanumeric string having 6 to 18 digits.',
            
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
            'amount.min'      => 'Minimum amount allowed is 10.',
            'amount.max'      => 'Maximum amount allowed is 49000.',
        
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
   public static function payOutAbhayam($request){
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
             'operator' => ['required','integer','in:336,337',$operatorRule],
            'bank_name' => $request['operator'] == 12 ? 'nullable|string|min:3|max:50' : 'required|string|min:3|max:50',
            'account_number' => $request['operator'] == 12 ? 'required|string|regex:/^[a-zA-Z0-9.\-_]{2,50}@[a-zA-Z]{3,64}$/' : 'required|string|regex:/^[A-Z0-9]{6,20}$/',
            'ifsc_code' => $request['operator'] == 12 ? 'nullable|string|regex:/^[A-Z]{4}0[A-Z0-9]{6}$/' : 'required|string|regex:/^[A-Z]{4}0[A-Z0-9]{6}$/',
            'mop' => $request['operator'] == 12 ? 'required|string|in:UPI' : 'required|string|in:NEFT,IMPS,RTGS',
            'latitude'  => 'required|numeric|between:-90,90',
            'longitude' => 'required|numeric|between:-180,180',
            'amount' => 'required|numeric|min:10|max:50000',
            'mobile_number'  => 'required|numeric|regex:/^[6-9][0-9]{9}$/u',
            'bene_id'       => 'required',
            // 'soliteck_id'      => 'required',
            // 'adhar_no'      => 'required|digits:12',
            //   'pan_no'        => 'required|string',
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
            'bank_name.regex'   => 'The bank_name must be numeric or alphanumeric string having 6 to 18 digits.',
            
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
            'amount.min'      => 'Minimum amount allowed is 10.',
            'amount.max'      => 'Maximum amount allowed is 49000.',
        
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
   public static function upiVerification($request){
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
            'mobile_number'   => 'required|digits:10|numeric',
            'account_number'  => 'required',
            // 'ben_id'   => 'required',
            // 'ifsc'     => 'required|regex:/(^[A-Za-z]{4}0[A-Z0-9a-z]{6}$)/u',
            'latitude' => 'required',
            'longitude'=> 'required',
            'beneficiary_name' => 'required',
            'client_ref'   => 'required|string|unique:verification_txns,client_ref',
            'mpin'     => 'required|numeric|digits:6',
             'operator' => ['required','integer','in:21',$operatorRule],
        ], [
            'mobile_number.required' => 'The remitter mobile number is required.',
            'mobile_number.digits'   => 'The remitter mobile number must be exactly 10 digits.',
            'mobile_number.numeric'  => 'The remitter mobile number must be numeric.',
    
            'ben_acc.required' => 'The beneficiary account number is required.',
            'ben_id.required'  => 'The beneficiary ID is required.',
            'ifsc.required'    => 'The IFSC code is required.',
            'ifsc.regex'       => 'The IFSC code format is invalid.',
    
            'latitude.required'  => 'Latitude is required.',
            'longitude.required' => 'Longitude is required.',
    
            'ben_name.required' => 'The beneficiary name is required.',
            'pf.required'       => 'PF is required.',
    
            'mpin.required' => 'The MPIN is required.',
            'mpin.numeric'  => 'The MPIN must be numeric.',
            'mpin.digits'   => 'The MPIN must be exactly 6 digits.',
            
            'operator.required' => 'Operator field is required.',
            'operator.integer'  => 'Operator must be a valid number.',
            'operator.between'  => 'Operator must be between 1 and 500.',
            'operator.exists'   => 'Selected operator does not exist or is inactive.',
            'operator.in'   => 'Selected operator is wrong.',
        ]);
    }
   public static function upiUtkal($request){
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
            'client_ref'  => 'required|unique:payout_txns|string|min:10|max:20',
    
            // UPI mandatory fields
         'account_number' => [
    'required',
    'string',
    'regex:/^([a-zA-Z0-9.\-_]{2,50}|[6-9]\d{9})@[a-zA-Z0-9]{3,64}$/'
],
            'beneficiary_name'   => 'required|string|min:3|max:255',
            'mobile_number'  => 'required|numeric|regex:/^[6-9][0-9]{9}$/u',
    
            // Location
            'latitude'  => 'required|numeric|between:-90,90',
            'longitude' => 'required|numeric|between:-180,180',
    
            // Transaction
            'amount' => 'required|numeric|min:100|max:50000',
            'mop'    => 'required|string|in:UPI',
    
            // Optional
            'email'     => 'nullable|email|max:150',
            'address'   => 'nullable|string|max:255',
            'purpose'   => 'sometimes|string',
    
            // Operator
            'operator'  => ['required', 'integer', 'in:338', $operatorRule],
    
            // MPIN required
            'mpin' => 'required|string|min:4|max:8',
    
        ],
        [
            'client_ref.required' => 'client_ref is required.',
            'client_ref.unique'   => 'client_ref must be unique.',
    
            'vpa.required' => 'VPA id is required.',
            'vpa.regex'    => 'Invalid VPA format.',
    
            'bene_name.required' => 'Beneficiary name is required.',
            'bene_name.min'      => 'Beneficiary name must be minimum 3 characters.',
    
            'mobile_number.required' => 'Mobile number is required.',
            'mobile_number.regex'    => 'Enter valid 10 digit mobile number.',
    
            'latitude.required'  => 'Latitude is required.',
            'longitude.required' => 'Longitude is required.',
    
            'amount.required' => 'Amount is required.',
            'amount.min'      => 'Minimum amount is ₹100.',
            'amount.max'      => 'Maximum amount is ₹50000.',
    
            'mop.required' => 'Mode of payment is required.',
            'mop.in'       => 'mop must be UPI only.',
    
            'email.email' => 'Enter a valid email address.',
    
            'operator.required' => 'Operator is required.',
            'operator.in'       => 'Invalid operator.',
            'operator.exists'   => 'Operator does not exist or inactive.',
    
            'mpin.required' => 'mpin is required.',
        ]);
    }
   public static function addBeneficiary($request){
        $user = Auth::user();
    
        return Validator::make($request, [
            'mobile_number' => 'required|digits:10|regex:/^[6-9][0-9]{9}$/',
            'bene_id'       => 'sometimes|required_without_all:bene_name,bene_account,bene_ifsc,bank_code,bene_pincode',
            'bene_name'     => 'sometimes|required_with:bene_account,bene_ifsc,bank_code,bene_pincode|string',
            'bank_name'     => 'required',
            'bene_account'  => 'required',
            'bank_code'     => 'required',
            'bene_pincode'  => 'sometimes|required_with:bene_name,bene_account,bene_ifsc,bank_code|digits:6',
        ], [
            'mobile_number.required' => 'Mobile number is required.',
            'mobile_number.digits'   => 'Mobile number must be exactly 10 digits.',
            'mobile_number.regex'    => 'Enter a valid 10-digit Indian mobile number.',
    
            'bene_id.required_without_all' => 'Either Bene ID or full beneficiary details must be provided.',
            
            'bene_name.required_with'      => 'Beneficiary name is required when adding a new beneficiary.',
            'bene_account.required_with'   => 'Beneficiary account is required when adding a new beneficiary.',
            'bene_ifsc.required_with'      => 'Beneficiary IFSC is required when adding a new beneficiary.',
            'bank_code.required_with'      => 'Bank code is required when adding a new beneficiary.',
            'bene_pincode.required_with'   => 'Beneficiary pincode is required when adding a new beneficiary.',
            'bene_pincode.digits'          => 'Beneficiary pincode must be exactly 6 digits.',
        ]);
    }
   public static function deleteBeneficiary($request){
        return Validator::make($request, [
            'mobile_number' => 'required|string|digits:10|regex:/^[6-9][0-9]{9}$/',
            'bene_id'       => 'required|integer',
        ], [
            'mobile_number.required' => 'Mobile number is required.',
            'mobile_number.digits'   => 'Mobile number must be exactly 10 digits.',
            'mobile_number.regex'    => 'Enter a valid 10-digit Indian mobile number.',
    
            'bene_id.required'       => 'Beneficiary ID is required.',
            'bene_id.integer'        => 'Beneficiary ID must be a valid number.',
        ]);
    }
   public static function verifyBeneficiary($request){
        return Validator::make($request, [
            'mobile_number' => 'required|digits:10',
            'bene_id'         => 'required',
            'bene_account'    => 'required',
            'bene_ifsc'       => 'required',
            'bank_code'       => 'required',
            'client_order_id' => 'required|numeric',
        ], [
            'mobile_number.required' => 'Mobile number is required.',
            'mobile_number.digits'   => 'Mobile number must be exactly 10 digits.',
            'mobile_number.regex'    => 'Enter a valid 10-digit Indian mobile number.',
    
            'bene_id.required'       => 'Beneficiary ID is required.',
            'bene_id.integer'        => 'Beneficiary ID must be a valid number.',
        ]);
    }
   public static function registerSenderSchema(){
        return [
            "formName" => "Register Sender",
            "fields" => [
                [
                    "name" => "sender_name",
                    "label" => "Sender Name",
                    "type" => "text",
                    "placeholder" => "Enter sender name",
                    "required" => true,
                    "validation" => [
                        "type"  => "string",
                        "min"   => 3,
                        "max"   => 50,
                        "regex" => "^[a-zA-Z\s]+$"
                    ]
                ],
                [
                    "name" => "mobile_number",
                    "label" => "Mobile Number",
                    "type" => "tel",
                    "placeholder" => "Enter 10-digit mobile number",
                    "required" => true,
                    "validation" => [
                        "type"   => "string",
                        "regex"  => "^[6-9][0-9]{9}$",
                        "unique" => "payout_senders,mobile_number"
                    ]
                ],
                // [
                //     "name" => "email",
                //     "label" => "Email",
                //     "type" => "email",
                //     "placeholder" => "Enter email address",
                //     "required" => true,
                //     "validation" => [
                //         "type"   => "email",
                //         "unique" => "payout_senders,email"
                //     ]
                // ]
            ]
        ];
    }
   public static function verifySenderSchema(){
        return [
            "formName" => "Verify Sender",
            "fields" => [
              
                [
                    "name" => "otp",
                    "label" => "OTP",
                    "type" => "number",
                    "placeholder" => "Enter 6-digit OTP",
                    "required" => true,
                    "validation" => [
                        "type" => "digits",
                        "length" => 6
                    ]
                ]
            ]
        ];
    }
}
