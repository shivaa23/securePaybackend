<?php
namespace App\Modules\Aeps\Models;

use Illuminate\Database\Eloquent\Model;
use Validator;
use Illuminate\Validation\Rule;
use Auth;
class Aeps2Validation extends Model
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
    public static function onboardingAeps2($request)
    {
      
        return Validator::make(
            $request,
            [
                'AadhaarNumber' => 'required|numeric|digits:12',
                'MobileNumber'  => 'required|numeric|digits:10',
                'AadhaarImage'  => 'required',
                'PanNumber'     => 'required',
                'PanImage'      => 'required',
                'ShopImage'     => 'required',
                'BankAccount'   => 'required',
                'Ifsc'          => 'required',
                'BankName'      => 'required',
                'FirstName'     => 'required|regex:/^[A-Za-z]{3,30}$/u',
                'LastName'      => 'required|regex:/^[A-Za-z]{3,30}$/u',
                'Address1'      => 'required',
                'Address2'      => 'required',
                'State'         => 'required',
                'City'          => 'required',
                'District'      => 'required',
                'PinCode'       => 'required',
                'ShopAddress'   => 'required',
                'ShopCity'      => 'required',
                'ShopDistrict'  => 'required',
                'ShopState'     => 'required',
                'ShopPinCode'   => 'required',
                'BusinessType'  => 'required',
                'Latitude'      => 'required|numeric|between:-90,90',
                'Longitude'     => 'required|numeric|between:-180,180',
            ],
            [
                'AadhaarNumber.required' => 'Aadhaar number is required.',
                'AadhaarNumber.numeric'  => 'Aadhaar must be numeric.',
                'AadhaarNumber.digits'   => 'Aadhaar must be exactly 12 digits.',
    
                'MobileNumber.required' => 'Mobile number is required.',
                'MobileNumber.numeric'  => 'Mobile number must be numeric.',
                'MobileNumber.digits'   => 'Mobile number must be exactly 10 digits.',
    
                'Latitude.required' => 'Latitude is required.',
                'Latitude.numeric'  => 'Latitude must be numeric.',
                'Latitude.between'  => 'Latitude must be between -90 and 90.',
    
                'Longitude.required' => 'Longitude is required.',
                'Longitude.numeric'  => 'Longitude must be numeric.',
                'Longitude.between'  => 'Longitude must be between -180 and 180.',
    
                'operator.required' => 'Operator is required.',
                'operator.integer'  => 'Operator must be a valid number.',
                'operator.in'       => 'Selected operator is not valid.',
                'operator.exists'   => 'Selected operator does not exist or is inactive.',
            ]
        );
    }
     public static function onboardingEkycFp($request)
     {
        return Validator::make(
            $request,
            [
                'mobileNumber' => 'required|numeric|digits:10',
                'aadharNumber' => 'required|numeric|digits:12',
                'panNumber'    => 'required|regex:/^([A-Z]{5})([0-9]{4})([A-Z]{1})$/u',
                'latitude'     => 'required|numeric|between:-90,90',
                'longitude'    => 'required|numeric|between:-180,180',
            ],
            [
                'aadharNumber.required' => 'Aadhaar number is required.',
                'aadharNumber.numeric'  => 'Aadhaar must be numeric.',
                'aadharNumber.digits'   => 'Aadhaar must be exactly 12 digits.',
    
                'mobileNumber.required' => 'Mobile number is required.',
                'mobileNumber.numeric'  => 'Mobile number must be numeric.',
                'mobileNumber.digits'   => 'Mobile number must be exactly 10 digits.',
    
                'latitude.required' => 'Latitude is required.',
                'latitude.numeric'  => 'Latitude must be numeric.',
                'latitude.between'  => 'Latitude must be between -90 and 90.',
    
                'longitude.required' => 'Longitude is required.',
                'longitude.numeric'  => 'Longitude must be numeric.',
                'longitude.between'  => 'Longitude must be between -180 and 180.',
                'panNumber.required' => 'PAN number is required.',
                'panNumber.regex'    => 'PAN must be in valid format (ABCDE1234F).',
            ]
        );
    }
     public static function getAeps2Schema($request)
     {
            return [
                "formName" => "AEPS 2 Onboarding",
                "fields" => [
                    [
                        "name" => "AadhaarNumber",
                        "label" => "Aadhaar Number",
                        "type" => "text",
                        "placeholder" => "Enter Aadhaar number",
                        "required" => true,
                        "validation" => [
                            "type" => "numeric",
                            "digits" => 12
                        ]
                    ],
                    [
                        "name" => "MobileNumber",
                        "label" => "Mobile Number",
                        "type" => "text",
                        "placeholder" => "Enter mobile number",
                        "required" => true,
                        "validation" => [
                            "type" => "numeric",
                            "digits" => 10
                        ]
                    ],
                    [
                        "name" => "AadhaarImage",
                        "label" => "Aadhaar Image",
                        "type" => "file",
                        "required" => true,
                        "validation" => [
                            "type" => "file"
                        ]
                    ],
                    [
                        "name" => "PanNumber",
                        "label" => "PAN Number",
                        "type" => "text",
                        "placeholder" => "Enter PAN number",
                        "required" => true
                    ],
                    [
                        "name" => "PanImage",
                        "label" => "PAN Image",
                        "type" => "file",
                        "required" => true
                    ],
                    [
                        "name" => "ShopImage",
                        "label" => "Shop Image",
                        "type" => "file",
                        "required" => true
                    ],
                    [
                        "name" => "BankAccount",
                        "label" => "Bank Account Number",
                        "type" => "text",
                        "placeholder" => "Enter bank account number",
                        "required" => true
                    ],
                    [
                        "name" => "Ifsc",
                        "label" => "IFSC Code",
                        "type" => "text",
                        "placeholder" => "Enter IFSC code",
                        "required" => true
                    ],
                    [
                        "name" => "BankName",
                        "label" => "Bank Name",
                        "type" => "text",
                        "placeholder" => "Enter bank name",
                        "required" => true
                    ],
                    [
                        "name" => "FirstName",
                        "label" => "First Name",
                        "type" => "text",
                        "placeholder" => "Enter first name",
                        "required" => true,
                        "validation" => [
                            "regex" => "/^[A-Za-z]{3,30}$/u"
                        ]
                    ],
                    [
                        "name" => "LastName",
                        "label" => "Last Name",
                        "type" => "text",
                        "placeholder" => "Enter last name",
                        "required" => true,
                        "validation" => [
                            "regex" => "/^[A-Za-z]{3,30}$/u"
                        ]
                    ],
                    [
                        "name" => "Address1",
                        "label" => "Address Line 1",
                        "type" => "text",
                        "required" => true
                    ],
                    [
                        "name" => "Address2",
                        "label" => "Address Line 2",
                        "type" => "text",
                        "required" => true
                    ],
                    [
                        "name" => "State",
                        "label" => "State",
                        "type" => "text",
                        "required" => true
                    ],
                    [
                        "name" => "City",
                        "label" => "City",
                        "type" => "text",
                        "required" => true
                    ],
                    [
                        "name" => "District",
                        "label" => "District",
                        "type" => "text",
                        "required" => true
                    ],
                    [
                        "name" => "PinCode",
                        "label" => "Pin Code",
                        "type" => "text",
                        "required" => true
                    ],
                    [
                        "name" => "ShopAddress",
                        "label" => "Shop Address",
                        "type" => "text",
                        "required" => true
                    ],
                    [
                        "name" => "ShopCity",
                        "label" => "Shop City",
                        "type" => "text",
                        "required" => true
                    ],
                    [
                        "name" => "ShopDistrict",
                        "label" => "Shop District",
                        "type" => "text",
                        "required" => true
                    ],
                    [
                        "name" => "ShopState",
                        "label" => "Shop State",
                        "type" => "text",
                        "required" => true
                    ],
                    [
                        "name" => "ShopPinCode",
                        "label" => "Shop Pin Code",
                        "type" => "text",
                        "required" => true
                    ],
                    [
                        "name" => "BusinessType",
                        "label" => "Business Type",
                        "type" => "text",
                        "required" => true
                    ],
                    [
                        "name" => "Latitude",
                        "label" => "Latitude",
                        "type" => "number",
                        "required" => true,
                        "validation" => [
                            "type" => "numeric",
                            "between" => [-90, 90]
                        ]
                    ],
                    [
                        "name" => "Longitude",
                        "label" => "Longitude",
                        "type" => "number",
                        "required" => true,
                        "validation" => [
                            "type" => "numeric",
                            "between" => [-180, 180]
                        ]
                    ],
                ]
            ];
        }

    public static function validateOtpFp($request)
    {
        return Validator::make(
            $request,
            [
                'otp' => 'required',
                'primaryKeyId' => 'required',
                'encodeFPTxnId' => 'required',
            ],
            [
                'otp.required' => 'OTP is required.',
                'primaryKeyId.required' => 'Primary Key ID is required.',
                'encodeFPTxnId.required' => 'Encoded FP Transaction ID is required.',
            ]
        );
    }
    public static function merchantBiometricKyc(array $request)
    {
        return Validator::make(
            $request,
            [
                'adhaarNumber'   => 'required|numeric|digits:12',
                'deviceId'       => 'required',
                'captureResponse'=> 'required|array',
                'primaryKeyId'   => 'required',
                'encodeFPTxnId'  => 'required',
            ],
            [
                'adhaarNumber.required'   => 'Aadhaar number is required.',
                'deviceId.required'       => 'Device ID is required.',
                'captureResponse.required'=> 'Capture Response is required.',
                'primaryKeyId.required'   => 'Primary Key ID is required.',
                'encodeFPTxnId.required'  => 'Encoded FP Transaction ID is required.',
            ]
        );
    }
	public static function cashWithdrawlFp($request)
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
        return Validator::make($request, [
            'AadharNumber'    => 'required|numeric|digits:12',
            'deviceId'        => 'required',
            'bank_iin'        => 'required',
            'number'          => 'required|numeric|digits:10',
            'amount'          => 'required|numeric|between:100,10000',
            'latitude'        => 'required',
            'longitude'       => 'required',
            // 'pf'              => 'required',
            'captureResponse' => 'required',
             'operator'      => ['required', 'integer','in:53', $operatorRule],
            'client_ref'    => 'required|unique:aeps_txns,client_ref',
        ],
         [
            'operator.required' => 'Operator field is required.',
            'operator.integer'  => 'Operator must be a valid number.',
            'operator.exists'   => 'Selected operator does not exist or is inactive.',
            'operator.in'       => 'Selected operator is wrong.',
        ]);
    }
    public static function addharPayFp($request)
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
        return Validator::make($request, [
            'AadharNumber'    => 'required|numeric|digits:12',
            'deviceId'        => 'required',
            'bank_iin'        => 'required',
            'number'          => 'required|numeric|digits:10',
            'amount'          => 'required|numeric|between:100,10000',
            'latitude'        => 'required',
            'longitude'       => 'required',
            // 'pf'              => 'required',
            'captureResponse' => 'required',
             'operator'      => ['required', 'integer','in:55', $operatorRule],
            'client_ref'    => 'required|unique:aeps_txns,client_ref',
        ],
         [
            'operator.required' => 'Operator field is required.',
            'operator.integer'  => 'Operator must be a valid number.',
            'operator.exists'   => 'Selected operator does not exist or is inactive.',
            'operator.in'       => 'Selected operator is wrong.',
        ]);
    }
    public static function balanceInquiryFp($request)
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
        return Validator::make($request, [
                        'AadharNumber'=>'required|numeric|digits:12',
						'deviceId'=>'required',
						'bank_iin'=>'required',
						'number'=>'required|numeric|digits:10',
						'latitude'=>'required',
						'longitude'=>'required',
				// 		'pf'=>'required',
                        'captureResponse'=>'required',
                        'operator'      => ['required', 'integer','in:52', $operatorRule],
                         'client_ref'    => 'required|unique:aeps_txns,client_ref',
        ],
         [
            'operator.required' => 'Operator field is required.',
            'operator.integer'  => 'Operator must be a valid number.',
            'operator.exists'   => 'Selected operator does not exist or is inactive.',
            'operator.in'       => 'Selected operator is wrong.',
        ]
        );
    }
    public static function miniStatementFp($request)
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
        return Validator::make($request, [
                        'AadharNumber'=>'required|numeric|digits:12',
						'deviceId'=>'required',
						'bank_iin'=>'required',
						'number'=>'required|numeric|digits:10',
						'latitude'=>'required',
						'longitude'=>'required',
				// 		'pf'=>'required',
                        'captureResponse'=>'required',
                        'operator'      => ['required', 'integer','in:54', $operatorRule],
                         'client_ref'    => 'required|unique:aeps_txns,client_ref',
        ],
         [
            'operator.required' => 'Operator field is required.',
            'operator.integer'  => 'Operator must be a valid number.',
            'operator.exists'   => 'Selected operator does not exist or is inactive.',
            'operator.in'       => 'Selected operator is wrong.',
        ]
        );
    }
    public static function twoFaFp($request)
{
    $user = Auth::user();

    return Validator::make($request, [
        'adhaarNumber'                  => 'required|numeric|digits:12',
        'deviceId'                      => 'required|string',
        'latitude'                      => 'required',
        'longitude'                     => 'required',

        // Capture Response Main
        'captureResponse'               => 'required|array',

        // Capture Response Nested Keys
        'captureResponse.errInfo'       => 'required|string',
        'captureResponse.fCount'        => 'required',
        'captureResponse.nmPoints'      => 'required',
        'captureResponse.qScore'        => 'required',

        'captureResponse.dpId'          => 'required|string',
        'captureResponse.rdsId'         => 'required|string',
        'captureResponse.rdsVer'        => 'required|string',

        'captureResponse.dC'            => 'required|string',
        'captureResponse.mI'            => 'required|string',
        'captureResponse.mC'            => 'required|string',
        'captureResponse.cI'            => 'required|string',

        'captureResponse.sessionKey'    => 'required|string',
        'captureResponse.hMac'          => 'required|string',

        'captureResponse.pidDataType'   => 'required|string',
        'captureResponse.pidData'       => 'required|string',
    ]);
}







}
