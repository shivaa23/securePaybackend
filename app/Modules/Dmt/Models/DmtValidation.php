<?php
namespace App\Modules\Dmt\Models;
use Illuminate\Database\Eloquent\Model;
use Validator;
use App\Modules\Integration\Models\Ipay;
use App\Modules\Integration\Models\Impsguru;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Auth;
class DmtValidation extends Model
{
    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
	//
    ];
    public static function remitterStatus($request){
        return Validator::make($request, [
            'mobile_number' => 'required|regex:/^[0-9]{10}$/u',

        ]);
	}
    public static function removeBeneficiary($request){
        return Validator::make($request, [
            'rem_mobile' => 'required|numeric|digits:10',
            'ben_id'     => 'required|string',
            
        ], [
            'rem_mobile.required' => 'The remitter mobile number is required.',
            'rem_mobile.numeric'  => 'The remitter mobile number must be numeric.',
            'rem_mobile.digits'   => 'The remitter mobile number must be 10 digits.',
            'ben_id.required'     => 'The beneficiary ID is required.',
            'ben_id.string'       => 'The beneficiary ID must be a valid string.',
            'id.required'         => 'The beneficiary primary ID is required.',
            'id.integer'          => 'The beneficiary primary ID must be a valid number.',
            'id.exists'           => 'The selected beneficiary record does not exist.',
        ]);
    }
    public static function dmtTxn($request){
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
            'mobile_number'       => 'required|digits:10',
            'amount'       => 'required|integer|between:1,5000',
            'ben_acc'      => 'required|string',
            'ben_id'       => 'required|string',
            'ifsc'         => ['required', 'regex:/^[A-Z]{4}0[A-Z0-9]{6}$/i'],
            'latitude'     => 'required|numeric|between:-90,90',
            'longitude'    => 'required|numeric|between:-180,180',
            'ben_name'     => 'required|string',
            "client_ref"   => 'required|string|unique:dmt_txns,client_ref',
            'type'         => 'required|string',
            'pf'           => 'required|string',
            'referenceKey' => 'required|string',
            'otp'          => 'required|digits:6',
            'mpin'         => 'required|digits:6',
             'operator' => ['required','integer','in:13',$operatorRule],
            
        ], [
             'number.required'   => 'Mobile number is required.',
            'number.digits'     => 'Please enter a valid 10-digit Indian mobile number.',
    
            'amount.required'   => 'Amount is required.',
            'amount.integer'    => 'Amount must be a number.',
            'amount.between'    => 'Amount must be between 1 and 5000.',
    
            'ben_acc.required'  => 'Beneficiary account number is required.',
            'ben_id.required'   => 'Beneficiary ID is required.',
            'ben_name.required' => 'Beneficiary name is required.',
    
            'ifsc.required'     => 'IFSC code is required.',
            'ifsc.regex'        => 'Please enter a valid IFSC code.',
    
            'latitude.required' => 'Latitude is required.',
            'latitude.numeric'  => 'Latitude must be a number.',
            'latitude.between'  => 'Latitude must be between -90 and 90.',
    
            'longitude.required' => 'Longitude is required.',
            'longitude.numeric'  => 'Longitude must be a number.',
            'longitude.between'  => 'Longitude must be between -180 and 180.',
    
            'referenceKey.required' => 'Reference key is required.',
    
            'otp.required'      => 'OTP is required.',
            'otp.digits'        => 'OTP must be exactly 6 digits.',
    
            'mpin.required'     => 'MPIN is required.',
            'mpin.digits'       => 'MPIN must be exactly 6 digits.',
            
            'operator.required' => 'Operator field is required.',
            'operator.integer'  => 'Operator must be a valid number.',
            'operator.between'  => 'Operator must be between 1 and 500.',
            'operator.exists'   => 'Selected operator does not exist or is inactive.',
            'operator.in'   => 'Selected operator is wrong.',
        ]);
    }
    public static function registerRemitter($request) {
        return Validator::make($request, [
            'mobile_number'   => 'required|regex:/^[0-9]{10}$/u',
            'aadhaar_number'  => 'required|numeric|digits:12',
            'reference_key'   => 'required|string',
        ], [
            'mobile_number.required'  => 'The mobile number is required.',
            'mobile_number.regex'     => 'The mobile number must be 10 digits.',
            'aadhaar_number.required' => 'The Aadhaar number is required.',
            'aadhaar_number.numeric'  => 'The Aadhaar number must be numeric.',
            'aadhaar_number.digits'   => 'The Aadhaar number must be exactly 12 digits.',
            'reference_key.required'  => 'The reference key is required.',
        ]);
    }
    public static function validateRemitter($request) {
        return Validator::make($request, [
            'rem_number'    => 'required|regex:/^[0-9]{10}$/u',
            'otp'           => 'required|numeric',
            'reference_key' => 'required',
        ], [
            'rem_number.required'    => 'The remitter mobile number is required.',
            'rem_number.regex'       => 'The remitter mobile number must be 10 digits.',
            'otp.required'           => 'The OTP is required.',
            'otp.numeric'            => 'The OTP must be numeric.',
            'reference_key.required' => 'The reference key is required.',
        ]);
    }
    public static function kycRemitter($request) {
       return Validator::make($request, [
        'mobile_number' => 'required|regex:/^[0-9]{10}$/u',
        'operator'      => 'required|numeric|in:15',
        'reference_key' => 'required',
        'latitude'      => 'required',
        'longitude'     => 'required',
        'pidData'       => 'required',
        'ci'            => 'required',
        'dc'            => 'required',
        'dpId'          => 'required',
        'hmac'          => 'required',
        'mc'            => 'required',
        'mi'            => 'required',
        'rdsId'         => 'required',
        'rdsVer'        => 'required',
        'sessionKey'    => 'required',
        'srno'          => 'required',
        'pf'            => 'required',
    ], [
        'mobile_number.required' => 'The remitter mobile number is required.',
        'mobile_number.regex'    => 'The remitter mobile number must be 10 digits.',
        'operator.required'      => 'The operator is required.',
        'operator.numeric'       => 'The operator must be numeric.',
        'operator.in'            => 'The operator value must be 15.',
        'reference_key.required' => 'The reference key is required.',
        'latitude.required'      => 'Latitude is required.',
        'longitude.required'     => 'Longitude is required.',
        'pidData.required'       => 'pidData is required.',
        'ci.required'            => 'ci is required.',
        'dc.required'            => 'dc is required.',
        'dpId.required'          => 'dpId is required.',
        'hmac.required'          => 'hmac is required.',
        'mc.required'            => 'mc is required.',
        'mi.required'            => 'mi is required.',
        'rdsId.required'         => 'rdsId is required.',
        'rdsVer.required'        => 'rdsVer is required.',
        'sessionKey.required'    => 'sessionKey is required.',
        'srno.required'          => 'srno is required.',
        'pf.required'            => 'pf is required.',
    ]);
}
    public static function registerBeneficiary($request) {
        return Validator::make($request, [
            'name'           => 'required|regex:/^[a-zA-Z\s]*$/',
            'rem_mobile'     => 'required|numeric|digits:10',
            'account_number' => 'required',
            'ifsc'           => 'required|regex:/^[A-Za-z]{4}0[A-Z0-9a-z]{6}$/',
            'bank_id'        => 'required',
        ], [
            'name.required'           => 'The beneficiary name is required.',
            'name.regex'              => 'The beneficiary name can only contain letters and spaces.',
            'rem_mobile.required'     => 'The remitter mobile number is required.',
            'rem_mobile.numeric'      => 'The remitter mobile number must be numeric.',
            'rem_mobile.digits'       => 'The remitter mobile number must be 10 digits.',
            'account_number.required' => 'The account number is required.',
            'ifsc.required'           => 'The IFSC code is required.',
            'ifsc.regex'              => 'The IFSC code format is invalid.',
            'bank_id.required'        => 'The bank ID is required.',
            'bank_id.exists'          => 'The selected bank does not exist.',
        ]);
    }
    public static function getBeneficiarySchema(Request $request){
        $data['ip'] = $request->ip();
        $url = 'https://api.impsguru.com/prod/v1/dmt/getBanks';
        $payload = '{"outlet_id": "'.Auth::user()->instId.'"}';
        $method = 'POST';
    
        $response = Impsguru::guruRequest($url, $method, $payload, 'GETBANKS');
    
        $banks = [];
        if (isset($response) && $response['statuscode'] == 'TXN') {
            $banks = collect($response['data'])
                ->map(function ($bank) {
                    return [
                        'bank_name' => $bank['name'],
                        'bank_id'   => $bank['bankId'],
                        'ifsc'      => $bank['ifscGlobal']
                    ];
                })
                ->unique('bank_name')  // remove duplicate bank names
                ->sortBy('bank_name')  // sort alphabetically
                ->values()             // reindex the array
                ->toArray();
        }
    
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
                    "validation" => [
                        "type" => "string"
                    ]
                ],
                [
                    "name" => "ifsc",
                    "label" => "IFSC Code",
                    "type" => "text",
                    "placeholder" => "Enter IFSC code",
                    "required" => true,
                    "validation" => [
                        "regex" => "/^[A-Za-z]{4}0[A-Z0-9a-z]{6}$/"
                    ]
                ],
                [
                    "name" => "name",
                    "label" => "Beneficiary Name",
                    "type" => "text",
                    "placeholder" => "Enter beneficiary name",
                    "required" => false,
                    "validation" => [
                        "type" => "string",
                        "regex" => "/^[a-zA-Z\s]*$/"
                    ]
                ],
                [
                    "name" => "account_number",
                    "label" => "Account Number",
                    "type" => "text",
                    "placeholder" => "Enter account number",
                    "required" => true,
                    "validation" => [
                        "type" => "string",
                        "regex" => "^[0-9]{9,18}$"
                    ]
                ],
                
            ]
        ];
    }
    public static function VerifyRegisterBeneficiary($request){
        return Validator::make($request, [
            'rem_mobile'     => 'required|numeric|digits:10',
            'ben_id'         => 'required|string',
            'otp'            => 'required|digits:6',
            'referenceKey'   => 'required|string',
             'mpin'         => 'required|digits:6',
        ], [
            'rem_mobile.required' => 'The remitter mobile number is required.',
            'rem_mobile.numeric'  => 'The remitter mobile number must be numeric.',
            'rem_mobile.digits'   => 'The remitter mobile number must be 10 digits.',
    
            'ben_id.required'     => 'The beneficiary ID is required.',
            'ben_id.string'       => 'The beneficiary ID must be a valid string.',
    
            'otp.required'        => 'The OTP is required.',
            'otp.digits'          => 'The OTP must be exactly 6 digits.',
    
            'name.required'       => 'The beneficiary name is required.',
            'name.regex'          => 'The beneficiary name can only contain letters and spaces.',
    
            'account_number.required' => 'The account number is required.',
    
            'ifsc.required'       => 'The IFSC code is required.',
            'ifsc.regex'          => 'The IFSC code format is invalid.',
    
            'bank_id.required'    => 'The bank ID is required.',
    
            'referenceKey.required' => 'The reference key is required.',
            'referenceKey.string'   => 'The reference key must be a valid string.',
        ]);
    }
    public static function VerifyRemoveBeneficiary($request){
        return Validator::make($request, [
            'rem_mobile'   => 'required|numeric|digits:10',
            'ben_id'       => 'required|string',
            'otp'          => 'required|digits:6',
            'referenceKey' => 'required|string',
             'mpin'         => 'required|digits:6',
            
        ], [
            'rem_mobile.required' => 'The remitter mobile number is required.',
            'rem_mobile.numeric'  => 'The remitter mobile number must be numeric.',
            'rem_mobile.digits'   => 'The remitter mobile number must be 10 digits.',
            
            'ben_id.required'     => 'The beneficiary ID is required.',
            'ben_id.string'       => 'The beneficiary ID must be a valid string.',
            
            'otp.required'        => 'The OTP is required.',
            'otp.digits'          => 'The OTP must be exactly 6 digits.',
            
            'referenceKey.required' => 'The reference key is required.',
            'referenceKey.string'   => 'The reference key must be a valid string.',
        ]);
    }
    public static function transactionOtpDmt1($request){
        return Validator::make($request, [
            'number'       => 'required|digits:10',
            'referenceKey' => 'required|string',
            'amount'       => 'required|numeric|min:1',
        ], [
            'number.required'       => 'The remitter mobile number is required.',
            'number.digits'         => 'The remitter mobile number must be exactly 10 digits.',
            'number.numeric'        => 'The remitter mobile number must be numeric.',
    
            'referenceKey.required' => 'The reference key is required.',
            'referenceKey.string'   => 'The reference key must be a valid string.',
    
            'amount.required'       => 'The amount is required.',
            'amount.numeric'        => 'The amount must be numeric.',
            'amount.min'            => 'The amount must be greater than zero.',
        ]);
    }
    public static function accountVerification($request){
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
            'ben_acc'  => 'required',
            // 'ben_id'   => 'required',
            'ifsc'     => 'required|regex:/(^[A-Za-z]{4}0[A-Z0-9a-z]{6}$)/u',
            'latitude' => 'required',
            'longitude'=> 'required',
            'ben_name' => 'required',
            'client_ref'   => 'required|string|unique:dmt_txns,client_ref',
            'mpin'     => 'required|numeric|digits:6',
             'operator' => ['required','integer','in:18',$operatorRule],
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
    public static function getRegisterRemitterSchema(Request $request){
        return [
            "formName" => "Register Remitter Schema",
            "fields" => [
                [
                    "name" => "mobile_number",
                    "label" => "Mobile Number",
                    "type" => "text",
                    "placeholder" => "Enter 10-digit mobile number",
                    "required" => true,
                    "validation" => [
                        "type" => "string",
                        "regex" => "/^[0-9]{10}$/"
                    ],
                    "messages" => [
                        "required" => "The mobile number is required.",
                        "regex" => "The mobile number must be 10 digits."
                    ]
                ],
                [
                    "name" => "aadhaar_number",
                    "label" => "Aadhaar Number",
                    "type" => "text",
                    "placeholder" => "Enter 12-digit Aadhaar number",
                    "required" => true,
                    "validation" => [
                        "type" => "numeric",
                        "digits" => 12
                    ],
                    "messages" => [
                        "required" => "The Aadhaar number is required.",
                        "numeric" => "The Aadhaar number must be numeric.",
                        "digits" => "The Aadhaar number must be exactly 12 digits."
                    ]
                ],
               
            ]
        ];
    }



}