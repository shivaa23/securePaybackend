<?php
namespace App\Modules\Aeps\Models;

use Illuminate\Database\Eloquent\Model;
use Validator;
use Illuminate\Validation\Rule;
use Auth;
class AepsValidation extends Model
{
    protected $fillable = [];

    /**
     * Initiate Signup Ipay Validation
     */
    public static function initiateSignupIpay($request)
    {
        return Validator::make(
            $request,
            [
                'mobile'        => 'required|regex:/^[0-9]{10}$/u',
                'pan'           => 'required|regex:/^([A-Z]){5}([0-9]){4}([A-Z]){1}$/u',
                'email'         => 'required|email',
                'bankAccountNo' => 'required|string|min:6|max:20',
                'bankIfsc'      => 'required|regex:/(^[A-Za-z]{4}0[A-Z0-9a-z]{6}$)/u',
                'aadhaar'       => 'required|regex:/^[0-9]{12}$/u',
                'latitude'      => 'required|numeric',
                'longitude'     => 'required|numeric',
            ],
            [
                'mobile.required' => 'Mobile number is required.',
                'mobile.regex'    => 'Mobile must be a valid 10-digit number.',

                'pan.required' => 'PAN number is required.',
                'pan.regex'    => 'Invalid PAN format (ABCDE1234F).',

                'email.required' => 'Email is required.',
                'email.email'    => 'Invalid email format.',

                'bankAccountNo.required' => 'Bank account number is required.',
                'bankAccountNo.min'      => 'Bank account number must be at least 6 digits.',
                'bankAccountNo.max'      => 'Bank account number cannot exceed 20 digits.',

                'bankIfsc.required' => 'IFSC code is required.',
                'bankIfsc.regex'    => 'Invalid IFSC format.',

                'aadhaar.required' => 'Aadhaar number is required.',
                'aadhaar.regex'    => 'Aadhaar must be a 12-digit number.',

                'latitude.required' => 'Latitude is required.',
                'latitude.numeric'  => 'Latitude must be numeric.',
                'latitude.between'  => 'Latitude must be between -90 and 90.',

                'longitude.required' => 'Longitude is required.',
                'longitude.numeric'  => 'Longitude must be numeric.',
                'longitude.between'  => 'Longitude must be between -180 and 180.',
            ]
        );
    }

    /**
     * Validate Signup Ipay (OTP step)
     */
    public static function validateSignupIpay($request)
    {
        return Validator::make(
            $request,
            [
                'otpReferenceID' => 'required|string',
                'hash'           => 'required|string',
                'hash2'          => 'required|string',
                'otp'            => 'required|numeric|digits:6',
                'latitude'      => 'required|numeric',
                'longitude'     => 'required|numeric',
            ],
            [
                'otpReferenceID.required' => 'OTP Reference ID is required.',
                'hash.required'           => 'Hash is required.',
                'hash2.required'          => 'Hash2 is required.',
                 'latitude.required' => 'Latitude is required.',
                'latitude.numeric'  => 'Latitude must be numeric.',
                'latitude.between'  => 'Latitude must be between -90 and 90.',

                'longitude.required' => 'Longitude is required.',
                'longitude.numeric'  => 'Longitude must be numeric.',
                'longitude.between'  => 'Longitude must be between -180 and 180.',
                'otp.required' => 'OTP is required.',
                'otp.numeric'  => 'OTP must be numeric.',
                'otp.digits'   => 'OTP must be exactly 6 digits.',
            ]
        );
    }
        public static function getInitiateSignupIpaySchema()
    {
        return [
            "formName" => "AePS Initiate Signup Schema",
            "fields" => [
                [
                    "name" => "mobile",
                    "label" => "Mobile Number",
                    "type" => "text",
                    "placeholder" => "Enter 10-digit mobile number",
                    "required" => true,
                    "validation" => ["type" => "regex", "pattern" => "^[0-9]{10}$"]
                ],
                [
                    "name" => "pan",
                    "label" => "PAN Number",
                    "type" => "text",
                    "placeholder" => "Enter PAN (ABCDE1234F)",
                    "required" => true,
                    "validation" => ["type" => "regex", "pattern" => "^([A-Z]){5}([0-9]){4}([A-Z]){1}$"]
                ],
                [
                    "name" => "email",
                    "label" => "Email",
                    "type" => "email",
                    "placeholder" => "Enter email",
                    "required" => true,
                    "validation" => ["type" => "email"]
                ],
                [
                    "name" => "bankAccountNo",
                    "label" => "Bank Account Number",
                    "type" => "text",
                    "placeholder" => "Enter bank account number",
                    "required" => true,
                    "validation" => ["type" => "string", "minLength" => 6, "maxLength" => 20]
                ],
                [
                    "name" => "bankIfsc",
                    "label" => "IFSC Code",
                    "type" => "text",
                    "placeholder" => "Enter IFSC (e.g., SBIN0001234)",
                    "required" => true,
                    "validation" => ["type" => "regex", "pattern" => "^[A-Za-z]{4}0[A-Z0-9a-z]{6}$"]
                ],
                [
                    "name" => "aadhaar",
                    "label" => "Aadhaar Number",
                    "type" => "text",
                    "placeholder" => "Enter 12-digit Aadhaar",
                    "required" => true,
                    "validation" => ["type" => "regex", "pattern" => "^[0-9]{12}$"]
                ],
             
            ]
        ];
    }
  
    public static function outletStatus($request)
    {
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
        return Validator::make(
            $request,
            [
                'AadharNumber' => 'required|numeric|digits:12',
                'pidDataType'  => 'required',
                'pidData'      => 'required',
                'ci'           => 'required',
                'dc'           => 'required',
                'dpId'         => 'required',
                'errInfo'      =>'sometimes',
                'fCount'       => 'required',
                'hmac'         => 'required',
                'mc'           => 'required',
                'mi'           => 'required',
                'nmPoints'     => 'required',
                'qScore'       => 'required',
                'rdsId'        => 'required',
                'rdsVer'       => 'required',
                'sessionKey'   => 'required',
                'srno'         => 'required',
                'operator' => ['required','integer','in:22',$operatorRule],
                'latitude'     => 'required|numeric|between:-90,90',
                'longitude'    => 'required|numeric|between:-180,180',
                'type'         => 'required',
                "client_ref"   => 'required|unique:aeps_txns,client_ref'
            ],
            [
                'AadharNumber.required' => 'Aadhaar number is required.',
                'AadharNumber.numeric'  => 'Aadhaar must be numeric.',
                'AadharNumber.digits'   => 'Aadhaar must be exactly 12 digits.',
    
                'latitude.required' => 'Latitude is required.',
                'latitude.numeric'  => 'Latitude must be numeric.',
                'latitude.between'  => 'Latitude must be between -90 and 90.',
    
                'longitude.required' => 'Longitude is required.',
                'longitude.numeric'  => 'Longitude must be numeric.',
                'longitude.between'  => 'Longitude must be between -180 and 180.',
                'operator.integer'  => 'Operator must be a valid number.',
                'operator.between'  => 'Operator must be between 1 and 500.',
                'operator.exists'   => 'Selected operator does not exist or is inactive.',
                'operator.in'   => 'Selected operator is wrong.',
                'type.required' => 'Type is required.',
            ]
        );
    }
   public static function balanceEnquiry($request)
    {
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
        return Validator::make(
            $request,
            [
                'number'       => 'required|numeric|digits:10',
                'AadhaarNumber'=> 'required|numeric|digits:12',
                'BankName'     => 'required|string',
                'bank_iin'     => 'required',
                'pidDataType'  => 'required',
                'pidData'      => 'required',
                'ci'           => 'required',
                'dc'           => 'required',
                'dpId'         => 'required',
                'errInfo'      =>'sometimes',
                'fCount'       => 'required',
                'hmac'         => 'required',
                'mc'           => 'required',
                'mi'           => 'required',
                'nmPoints'     => 'required',
                'qScore'       => 'required',
                'rdsId'        => 'required',
                'rdsVer'       => 'required',
                'sessionKey'   => 'required',
                'srno'         => 'required',
                'pf'           => 'required',
                'operator' => ['required','integer','in:49,51',$operatorRule],
                'latitude'     => 'required|numeric|between:-90,90',
                'longitude'    => 'required|numeric|between:-180,180',
                "client_ref"   => 'required|unique:aeps_txns,client_ref'
            ],
            [
                'AadhaarNumber.required' => 'Aadhaar number is required.',
                'AadhaarNumber.numeric'  => 'Aadhaar must be numeric.',
                'AadhaarNumber.digits'   => 'Aadhaar must be exactly 12 digits.',
    
                'latitude.required' => 'Latitude is required.',
                'latitude.numeric'  => 'Latitude must be numeric.',
                'latitude.between'  => 'Latitude must be between -90 and 90.',
    
                'longitude.required' => 'Longitude is required.',
                'longitude.numeric'  => 'Longitude must be numeric.',
                'longitude.between'  => 'Longitude must be between -180 and 180.',
                 'operator.required' => 'Operator field is required.',
                'operator.integer'  => 'Operator must be a valid number.',
                'operator.between'  => 'Operator must be between 1 and 500.',
                'operator.exists'   => 'Selected operator does not exist or is inactive.',
                'operator.in'   => 'Selected operator is wrong.',
            ]
        );
    }
   public static function aadharPay($request)
    {
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
    
        return Validator::make(
            $request,
            [
                'number'        => 'required|numeric|digits:10',
                'amount'        => 'required|numeric|between:99,10000',
                'AadhaarNumber' => 'required|numeric|digits:12',
                'BankName'      => 'required|string',
                'bank_iin'      => 'required',
                'pidDataType'   => 'required',
                'pidData'       => 'required',
                'ci'            => 'required',
                'dc'            => 'required',
                'dpId'          => 'required',
                'fCount'        => 'required',
                'hmac'          => 'required',
                'mc'            => 'required',
                'mi'            => 'required',
                'nmPoints'      => 'required',
                'qScore'        => 'required',
                'rdsId'         => 'required',
                'rdsVer'        => 'required',
                'sessionKey'    => 'required',
                'errInfo'      =>'required',
                'srno'          => 'required',
                'pf'            => 'required',
                'latitude'      => 'required|numeric|between:-90,90',
                'longitude'     => 'required|numeric|between:-180,180',
                'operator'      => ['required', 'integer', 'in:49', $operatorRule],
                'client_ref'    => 'required|unique:aeps_txns,client_ref',
            ],
            [
                'AadhaarNumber.required' => 'Aadhaar number is required.',
                'AadhaarNumber.numeric'  => 'Aadhaar must be numeric.',
                'AadhaarNumber.digits'   => 'Aadhaar must be exactly 12 digits.',
    
                'latitude.required' => 'Latitude is required.',
                'latitude.numeric'  => 'Latitude must be numeric.',
                'latitude.between'  => 'Latitude must be between -90 and 90.',
    
                'longitude.required' => 'Longitude is required.',
                'longitude.numeric'  => 'Longitude must be numeric.',
                'longitude.between'  => 'Longitude must be between -180 and 180.',
    
                'operator.required' => 'Operator field is required.',
                'operator.integer'  => 'Operator must be a valid number.',
                'operator.exists'   => 'Selected operator does not exist or is inactive.',
                'operator.in'       => 'Selected operator is wrong.',
            ]
        );
    }
     public static function cashWithdrawl($request)
{
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

    return Validator::make(
        $request,
        [
            'number'        => 'required|numeric|digits:10',
            'amount'        => 'required|numeric|between:99,10000',
            'AadhaarNumber' => 'required|numeric|digits:12', // ✅ fixed name
            'BankName'      => 'required|string',
            'bank_iin'      => 'required',
            'pidDataType'   => 'required',
            'pidData'       => 'required',
            'ci'            => 'required',
            'dc'            => 'required',
            'dpId'          => 'required',
            'fCount'        => 'required',
            'hmac'          => 'required',
            'mc'            => 'required',
            'mi'            => 'required',
            'nmPoints'      => 'required',
            'qScore'        => 'required',
            'rdsId'         => 'required',
            'rdsVer'        => 'required',
            'sessionKey'    => 'required',
            'srno'          => 'required',
            'pf'            => 'required',
            'latitude'      => 'required|numeric|between:-90,90',   // ✅ fixed
            'longitude'     => 'required|numeric|between:-180,180', // ✅ fixed
            'operator'      => ['required', 'integer','in:50', $operatorRule],
            'client_ref'    => 'required|unique:aeps_txns,client_ref',
        ],
        [
            'AadhaarNumber.required' => 'Aadhaar number is required.',
            'AadhaarNumber.numeric'  => 'Aadhaar must be numeric.',
            'AadhaarNumber.digits'   => 'Aadhaar must be exactly 12 digits.',

            'latitude.required' => 'Latitude is required.',
            'latitude.numeric'  => 'Latitude must be numeric.',
            'latitude.between'  => 'Latitude must be between -90 and 90.',

            'longitude.required' => 'Longitude is required.',
            'longitude.numeric'  => 'Longitude must be numeric.',
            'longitude.between'  => 'Longitude must be between -180 and 180.',

            'operator.required' => 'Operator field is required.',
            'operator.integer'  => 'Operator must be a valid number.',
            'operator.exists'   => 'Selected operator does not exist or is inactive.',
            'operator.in'       => 'Selected operator is wrong.',
        ]
    );
}




}
