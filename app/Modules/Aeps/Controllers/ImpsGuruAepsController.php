<?php

namespace App\Modules\Aeps\Controllers;

use Laravel\Lumen\Routing\Controller as BaseController;
use App\Traits\ApiResponser;
use Illuminate\Http\Request;
use App\Modules\Aeps\Models\AepsTxn;
use App\Modules\Aeps\Models\AepsValidation;
use App\Helper\ResponseHelper;
use App\Modules\Integration\Models\Ipay;
use Validator;
use App\Modules\User\Models\IpayDetail;
use Auth;
use App\Modules\AuditLog\Models\AuditLog;
use App\Modules\Aeps\Services\AepsResponseHandler;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\DB;
use App\Modules\WalletLedger\Services\WalletService;
use App\Modules\AuditLog\Services\AuditService;
use App\Modules\Aeps\Services\AepsService; 
use App\Modules\Aeps\Models\AepsPayloadModel;
use App\Modules\Aeps\Models\AepsBank;
use App\Modules\Auth\Models\Otp;
use App\Modules\Service\Models\Service;
use App\Modules\User\Models\User;
use App\Modules\Commission\Models\AcommRule;
use App\Modules\Commission\Models\CommRule;
use App\Modules\Integration\Models\Impsguru;
use App\Modules\Misc\Models\Uuid;
use Illuminate\Support\Facades\Log;
class ImpsGuruAepsController extends BaseController
{
    use ApiResponser;
    public function __construct(){
        $this->middleware('auth', ['except' => []]);
        $this->middleware('check.active', ['except' => []]);
        $this->middleware('check.service.time',['except' => '']);
    }
    /**
     * Fetch AEPS Transactions
     */
    //public function getAepsTxns(Request $request) // {
    //     $user = Auth::user();
    //     $query = AepsTxn::with('user');
    //     if (!in_array($user->role, ['sadm', 'adm'])) {
    //         $userIds = User::getDescendantUserIds($user->id);
    //         $query->whereIn('user_id', empty($userIds) ? [$user->id] : $userIds);
    //     }
    //     if ($request->filled('status')) {
    //         $query->where('status', strtoupper($request->status));
    //     }
    //     if ($request->filled('user_id')) {
    //         $query->where('user_id', $request->user_id);
    //     }
    //     if ($request->filled('route')) {
    //         $query->where('route', $request->route);
    //     }
    //     if ($request->filled('client_ref')) {
    //         $query->where('client_ref', $request->client_ref);
    //     }
    //     if ($request->filled('txn_id')) {
    //         $query->where('txn_id', $request->txn_id);
    //     }
    
    //     if ($request->input('export') == 1) {
    //         $txns = $query->latest()->get();
    //         $txns->transform(function ($txn) {
    //             $txn->establishment = $txn->user->establishment ?? 'Unknown';
    //             unset($txn->user);
    //             return $txn;
    //         });
    //         return ResponseHelper::success('Transactions fetched', $txns, 200);
    //     }
    
    //     $paginate = $request->input('paginate', 15);
    //     $txns = $query->latest()->paginate($paginate);
    
    //     $txns->getCollection()->transform(function ($txn) {
    //         $txn->establishment = $txn->user->establishment ?? 'Unknown';
    //         unset($txn->user);
    //         return $txn;
    //     });
    
    //     return ResponseHelper::success('Transactions fetched', $txns, 200);
    // }
    public function getAepsTxns(Request $request) {

    $user = Auth::user();
    $query = AepsTxn::with('user');

    $allFields = [
        'id','user_id','txn_id','client_ref','operator','route','pf','aadhaar_number','iin','bank_name',
        'txn_type','amount','ret_comm','charges','di_comm','md_comm','a_comm','ret_tds','di_tds','md_tds',
        'balance','status','rrn','api_response','created_at','updated_at'
    ];

    $hideForMD  = ['route','api_response','a_comm'];  
    $hideForDI  = ['md_comm','md_tds','a_comm']; 
    $hideForRET = ['di_comm','di_tds','md_comm','md_tds','a_comm'];  

    switch ($user->role) {

        case 'sadm':
        case 'adm':
            $allowed = $allFields;
            break;

        case 'md':
            $userIds = array_merge([$user->id], User::getDescendantUserIds($user->id) ?? []);
            $query->whereIn('user_id', $userIds);

            $allowed = array_diff($allFields, $hideForMD);
            break;

        case 'di':
            $userIds = array_merge([$user->id], User::getDescendantUserIds($user->id) ?? []);
            $query->whereIn('user_id', $userIds);

            $allowed = array_diff($allFields, $hideForDI);
            break;

        case 'dd':
        case 'api':
        case 'ret':
            $query->where('user_id', $user->id);

            $allowed = array_diff($allFields, $hideForRET);
            break;

        default:
            return ResponseHelper::error('Unauthorized role access', [], 403);
    }

    // 🔥 Apply final select AFTER role-based filters
    $query->select($allowed);

    // --------- FILTERS -----------
    $query
        ->when($request->filled('status'), fn($q) =>
            $q->where('status', strtoupper($request->status))
        )
        ->when($request->filled('mobile_number'), fn($q) =>
            $q->where('mobile_number', $request->mobile_number)
        )
        ->when($request->filled('account_number'), fn($q) =>
            $q->where('account_number', $request->account_number)
        )
        ->when($request->filled('user_id'), fn($q) =>
            $q->where('user_id', $request->user_id)
        )
        ->when($request->filled('route'), fn($q) =>
            $q->where('route', $request->route)
        )
        ->when($request->filled('txn_id'), fn($q) =>
            $q->where('txn_id', $request->txn_id)
        )
        ->when($request->filled('service'), fn($q) =>
            $q->where('operator', $request->service)
        )
        ->when($request->filled(['from_date', 'to_date']), fn($q) =>
            $q->whereBetween('created_at', [
                $request->from_date . ' 00:00:00',
                $request->to_date . ' 23:59:59'
            ])
        );

    // -------- EXPORT MODE ----------
    if ($request->boolean('export')) {
  ini_set('memory_limit', '512M');
        $txns = $query->latest()->get()->map(function ($txn) {
            $txn->establishment = $txn->user->establishment ?? null;
            unset($txn->user);
            return $txn;
        });

        return ResponseHelper::success('Transactions fetched', $txns, 200);
    }

    // -------- PAGINATION ----------
    $paginate = $request->input('paginate', 15);

    $txns = $query->latest()->paginate($paginate);

    $txns->getCollection()->transform(function ($txn) {
        $txn->establishment = $txn->user->establishment ?? null;
        unset($txn->user);
        return $txn;
    });

    return ResponseHelper::success('Transactions fetched', $txns, 200);
}
    public function getBanks(){
        $banks = AepsBank::select('bank_name','branch_ifsc','bank_iin')->get();
        return ResponseHelper::success(
            "Bank fetched",
             $banks
        ,200);
    }
    public function initiateSignupAeps(Request $request){
        $user = Auth::user();
    
        // Validation
        $validator = AepsValidation::initiateSignupIpay($request->all());
        if ($validator->fails()) {
            return ResponseHelper::error($validator->errors(),'Validation Failed',  400);
        }
        $data = $request->all();
        $data['ip'] = $request->ip();
        $data['url'] = 'https://api.impsguru.com/prod/v1/onboarding/onboarding1';
        $payload = [
            "mobileNumber"        => $data['mobile'],
            "panNumber"           => $data['pan'],
            "email"         => $data['email'],
            "bankAccountNo" => $data['bankAccountNo'],
            "bankIfsc"      => $data['bankIfsc'],
            "outlet_id"     => $user->instId,
            "aadhaarNumber"       =>$request->aadhaar,
            "latitude"      => $data['latitude'],
            "longitude"     => $data['longitude'],
            "consent"       => "Y"
        ];
     
        $response = Impsguru::guruRequest($data['url'], 'POST', json_encode($payload), '');
    //  return $response;
        if (!$response) {
            return ResponseHelper::error(
                'No Response Received. UIDAI might be down, please try again later',
                [],
                503
            );
        }
        if (isset($response['response']['statuscode']) && $response['response']['statuscode'] === 'TXN') {
            $res = [
                'otpReferenceID' => $response['response']['data']['otpReferenceID'] ?? null,
                'hash'           => $response['response']['data']['hash'] ?? null,
            ];
            return ResponseHelper::success('OTP Sent', $res, 200);
        }
    
        return ResponseHelper::error($response['response']['message'] ?? 'Failed', $response['response'], 403);
    }
    // public function validateSignupAeps(Request $request){
    //     $user = Auth::user();
    
    //     $validator = AepsValidation::validateSignupIpay($request->all());
    //     if ($validator->fails()) {
    //         return ResponseHelper::error( $validator->errors(),'Validation Failed', 400);
    //     }
    
    //     $data = $request->all();
    //     $hash = $data['hash2'] ?? $data['hash'];
    
    //     $payload = [
    //         "otpReferenceID" => $data['otpReferenceID'],
    //         "otp"            => $data['otp'],
    //         "hash"           => $hash
    //     ];
    
    //     $apiData = [
    //         'ip'      => $request->ip(),
    //         'url'     => 'https://api.impsguru.com/prod/v1/onboarding/validateOnboarding1',
    //         'payload' => json_encode($payload),
    //         'method'  => 'POST'
    //     ];
    
    //     $response = Impsguru::guruRequest($apiData['url'], $apiData['method'], $apiData['payload'], '');
        
    //     AuditLog::create([
    //         'user_id'      => $user->id,
    //         'role'         => $user->role,
    //         'action'       => 'Validate Aeps Outlet',
    //         'service_name' => 'auth',
    //         'request_data' => json_encode($request->all()),
    //         'response_data'=> json_encode($response),
    //         'ip_address'   => $request->ip(),
    //         'user_agent'   => $request->header('User-Agent'),
    //         'status'       => $response ? 'Success' : 'Failed'
    //     ]);
    
    //     if (!$response) {
    //         return ResponseHelper::error(
    //             'No Response Received. UIDAI might be down, please try again later',
    //             [],
    //             503
    //         );
    //     }
    
    //     if (($response['response']['statuscode'] ?? '') !== 'TXN') {
    //         return ResponseHelper::error($response['response']['message'], $response['response'], 403);
    //     }
    
    //     $oldUser = User::where('instId', $response['response']['data']['outletId'] ?? null)->first();
    //     if ($oldUser) {
    //         return ResponseHelper::error('User with this KYC already exists, try another KYC', [], 409);
    //     }
    //     DB::beginTransaction();
    //     try{
    //     $user->instId = $response['response']['data']['outletId'] ?? null;
    //     $user->save();
    //     $ipayDetail = IpayDetail::where('user_id', $user->id)->first();
    //     if ($ipayDetail) {
    //         $ipayDetail->update([
    //             'ipay_id'   => $response['response']['data']['outletId'] ?? null,
    //             'instid'    => $response['response']['data']['outletId'] ?? null,
    //             'latitude'  => $request->latitude ?? null,
    //             'longitude' => $request->longitude ?? null,
    //             'kyc_res'   => $response,
    //         ]);
    //     } else {
    //         IpayDetail::create([
    //             'ipay_id'   => $response['response']['data']['outletId'] ?? null,
    //             'user_id'   => $user->id,
    //             'instid'    => $response['response']['data']['outletId'] ?? null,
    //             'latitude'  => $request->latitude ?? null,
    //             'longitude' => $request->longitude ?? null,
    //             'kyc_res'   => $response,
    //         ]);
    //     }
    // } catch (\Exception $e) {
    //     DB::rollBack();
    //     return ResponseHelper::error('Transaction Update Failed', $response, 500);
    // }
    
    // return ResponseHelper::success('User Verified Successfully', [], 200);
    // }
    public function validateSignupAeps(Request $request){
        $user = Auth::user();
    
        $validator = AepsValidation::validateSignupIpay($request->all());
        if ($validator->fails()) {
            return ResponseHelper::error($validator->errors(), 'Validation Failed', 400);
        }
    
        $data = $request->all();
        $hash = $data['hash2'] ?? $data['hash'];
    
        $payload = [
            "otpReferenceID" => $data['otpReferenceID'],
            "otp"            => $data['otp'],
            "hash"           => $hash
        ];
    
        $response = Impsguru::guruRequest(
            'https://api.impsguru.com/prod/v1/onboarding/validateOnboarding1',
            'POST',
            json_encode($payload),
            ''
        );
    
        AuditLog::create([
            'user_id'      => $user->id,
            'role'         => $user->role,
            'action'       => 'Validate Aeps Outlet',
            'service_name' => 'auth',
            'request_data' => json_encode($request->all()),
            'response_data'=> json_encode($response),
            'ip_address'   => $request->ip(),
            'user_agent'   => $request->header('User-Agent'),
            'status'       => $response ? 'Success' : 'Failed'
        ]);
    
        // ✅ If API didn't respond
        if (!$response) {
            return ResponseHelper::error(
                'No Response Received. UIDAI might be down, please try again later',
                [],
                503
            );
        }
    
        // ✅ If AEPS response is not successful
        if (($response['response']['statuscode'] ?? '') !== 'TXN') {
            return ResponseHelper::error(
                $response['response']['message'] ?? 'Something went wrong',
                $response['response'] ?? [],
                403
            );
        }
    
        // ✅ If user already exists
        $oldUser = User::where('instId', $response['response']['data']['outletId'] ?? null)->first();
        // if ($oldUser) {
        //     return ResponseHelper::error(
        //         'User with this KYC already exists, try another KYC',
        //         [],
        //         409
        //     );
        // }
    
        DB::beginTransaction();
        try {
            $outletId = $response['response']['data']['outletId'] ?? null;
    
            $user->instId = $outletId;
            $user->save();
    
            IpayDetail::updateOrCreate(
                ['user_id' => $user->id],
                [
                    'ipay_id'   => $outletId,
                    'instid'    => $outletId,
                    'latitude'  => $request->latitude,
                    'longitude' => $request->longitude,
                    'kyc_res'   => $response,
                ]
            );
    
            DB::commit();   
            return ResponseHelper::success('User Verified Successfully', [], 200); 
        }
        catch (\Exception $e) {
            DB::rollBack();
            return ResponseHelper::error('Transaction Update Failed', $e->getMessage(), 500);
        }
    }
    public function getInitiateSchema(){
        $schema=AepsValidation::getInitiateSignupIpaySchema();
        return ResponseHelper::success('AePS Initiate Signup Schema', $schema ,200);
    }
    public function outletLoginStatus(Request $request) {
        try {
            $user = Auth::user();
        //   return "sdkjsd";
            $data['ip'] = $request->ip();
    
            $url = 'https://api.impsguru.com/prod/v1/aeps/outletLoginStatus';
            $outlet_id = $user->instId;
    
            // Correct payload with JSON encode
            $payload = [
                "outlet_id" => $outlet_id,
            ];
        //  return $payload;
            $response = Impsguru::guruRequest($url, 'POST', json_encode($payload),'');
        //   return $response;
            // Log the request/response
            AuditLog::create([
                'user_id'       => $user->id,
                'role'          => $user->role,
                'action'        => 'AEPS Outlet login status',
                'service_name'  => 'auth',
                'request_data'  => json_encode($request->all()),
                'response_data' => json_encode($response),
                'ip_address'    => $request->ip(),
                'user_agent'    => $request->header('User-Agent'),
                'status'        => isset($response['statuscode']) && $response['statuscode'] === 'TXN' ? 'Success' : 'Failed'
            ]);
    
            if (!$response) {
                return ResponseHelper::error('Service Provider Error', [], 403);
            }
        //  return $response['response']['statuscode'];
            if (isset($response['response']['statuscode']) && $response['response']['statuscode'] === 'TXN') {
                return ResponseHelper::success(
                    
                    'SUCCESS'
                    , 
                   $response['response']
                , 200);
            }
    
            return ResponseHelper::error( $response['response'] ?? 'Unknown Error', [], 403);
    
        } catch (\Exception $e) {
            return ResponseHelper::error('Something went wrong', [
                'exception' => $e->getMessage()
            ], 500);
        }
    }
    public function outletLogin(Request $request){
    
        $user = Auth::user();
        // $dupCheck = Uuid::check($request->client_ref,'outletLogin Aeps1',$request);
        // if($dupCheck['code'] != '200'){
        //     return ResponseHelper::error('Duplicate Transaction',[],402);
        // }
        $validator = AepsValidation::outletStatus($request->all());
      
        if ($validator->fails()) {
            return ResponseHelper::error('Validation Failed', $validator->errors(), 400);
        }
    
    
        $ipayDetails = IpayDetail::where("user_id", $user->id)->first();
        if (!$ipayDetails) {
            return ResponseHelper::error('Please complete outlet registration', [], 403);
        }
    
        $data = $request->all();
        if (!is_null($ipayDetails->latitude) && !is_null($ipayDetails->longitude)) {
            $data['latitude'] = $ipayDetails->latitude;
            $data['longitude'] = $ipayDetails->longitude;
        } else{
            return ResponseHelper::error('Location Details Not Found', [], 403);
        }
        $data['outlet_id']=$user->instId;
        // // Verify MPIN
        // if (!OTP::verifyMpin($request->mpin)) {
        //     return ResponseHelper::error("Invalid MPIN", [], 403);
        // }
    
        // Role check
        $amount=1;
        if (!in_array($user->role, ["api", "dd", "ret"])) {
            return ResponseHelper::error('Action not Authorised', '', 403);
        }
        if ($user->w1 < ($amount * 100)) {
            return ResponseHelper::error('Insufficient W1 Balance', '', 403);
        }
    
        // Service & Commission
        $service = Service::find($request->operator);
        //return $service;
        if (!$service) {
            return ResponseHelper::error('Invalid Operator/Service', [], 400);
        }
      
        $comm = CommRule::calculateCommission($user, $service->name, $amount);
        if(!$comm){
            return ResponseHelper::error('Retailer Commission Slab Not Configured', [], 400);
        }
        $comm = CommRule::finalCommission($comm, $amount,$service);
    
        // Encrypt Aadhaar
        $aadhaarNumber = $data['AadharNumber'] ?? null;
        if (empty($aadhaarNumber)) {
            return ResponseHelper::error('AadharNumber is required', [], 400);
        }
        $encryptedData = AepsPayloadModel::encryptAadhaar($aadhaarNumber);
        $payload = AepsPayloadModel::buildPayload($data, $encryptedData);
        // return $payload;
        DB::beginTransaction();
        try {
            $txn = AepsService::create($user, $service, $request, $comm,$amount);
            WalletService::debit($user, $request->amount, $txn, $service);
            $auditLog = AuditService::logRequest($user, $request, $service);
            DB::commit();
        } catch (\Exception $e) {
            DB::rollBack();
            return ResponseHelper::error('Transaction Init Failed', $e->getMessage(), 500);
        }
        $url='https://api.impsguru.com/prod/v1/aeps/outletLogin';
        try {
            // return "sdjhsds";
      $response = Impsguru::guruRequest($url, 'POST', $payload,'');
        //   return $response;
            // return $response;
        } catch (\Exception $e) {
            return ResponseHelper::error('Ipay Service Error', $e->getMessage(), 500);
        }
        DB::beginTransaction();
        try {
           $result= AepsResponseHandler::handleOutlet($txn, $response["response"], $user, $service, $comm);
            DB::commit();
            return $result;
        } catch (\Exception $e) {
            DB::rollBack();
            return ResponseHelper::error('Transaction Update Failed', $e->getMessage(), 500);
        }
        return response()->json([
            'status'  => $status,
            'message' => $response['message'] ?? 'API responded',
            'data'    => $response
        ],  200 );
    }
//   public function outletStatusIpay(Request $request)
// {
//     $user = Auth::user();

//     $data['ip'] = $request->ip();
//     $data['url'] = 'https://api.impsguru.com/prod/v1/aeps/outletStatusIpay';
//     $data['payload'] = json_encode([
//         "spkey" => "WAP"
//     ]);
//     $data['method'] = 'POST';
//     $data['outlet_id'] = $user->instId;

//     $response = InstantPay::ipayStatusCheck($data);

//     // Log response
//     $log = ['response' => $response, 'fileName' => 'signup'];
//     Logs::logs($log);
//     if (!$response) { 
//         return $this->jsonResponse( 'ERR','Failed','No Response Received. UIDAI server may be down. Please try later.', null,503);
//     }
//     if ($response['statuscode'] != 'TXN') {
//         return $this->jsonResponse('ERR','Failed',$response['status'],$response,403);
//     }
//     if (!isset($response['data']['outletId'])) { 
//         return $this->jsonResponse( 'ERR','Failed','Outlet data not found',$response,404 );
//     }
//     return $this->jsonResponse( 'TXN','Success', $response['actcode']??null,$response,  200);
//   }
    public function outletStatusIpay(Request $request)
    {
        try {
            $user = Auth::user();
    
            $url = 'https://api.impsguru.com/prod/v1/aeps/outletStatusIpay';
    
            $payload = [
                "spkey"     => "WAP",
                "outlet_id" => $user->instId
            ];
    
            $response = Impsguru::guruRequest($url, 'POST', json_encode($payload), '');
    
            $statusCode = $response['response']['statuscode'] ?? null;
            $finalData  = $response['response']['data']['data'] ?? [];
            $message    = $finalData['action'] ?? 'Unknown';
    
            // Status Update Logic
            if ($statusCode === 'TXN') {
                if (($finalData['action'] ?? '') === "NO-ACTION-REQUIRED") {
                    $user->outlet_status = 1;
                    $user->save();
                }
            }
    
            // Audit Log
            AuditLog::create([
                'user_id'       => $user->id,
                'role'          => $user->role,
                'action'        => 'Outlet status',
                'service_name'  => 'auth',
                'request_data'  => json_encode($payload),
                'response_data' => json_encode($response),
                'ip_address'    => $request->ip(),
                'user_agent'    => $request->header('User-Agent'),
                'status'        => $statusCode === 'TXN' ? 'Success' : 'Failed'
            ]);
    
            return $statusCode === 'TXN'
                ? ResponseHelper::success($message, $finalData, 200)
                : ResponseHelper::error($message, $finalData, 403);
    
        } catch (\Exception $e) {
            return ResponseHelper::error(
                'Something went wrong',
                ['exception' => $e->getMessage()],
                500
            );
        }
    }

    public function merchantBiomatricKycIpay(Request $request)
    {
        $rules = [
            'referenceKey' => 'required',
            'latitude' => 'required',
            'longitude' => 'required',
            'AadharNumber' => 'required',
            'dc' => 'required',
            'ci' => 'required',
            'hmac' => 'required',
            'dpId' => 'required',
            'mc' => 'required',
            'pidDataType' => 'required',
            'rdsId' => 'required',
            'sessionKey' => 'required',
            'mi' => 'required',
            'ts' => 'required',
            'pidData' => 'required',
            'rdsVer' => 'required',
        ];
    
        $validator = Validator::make($request->all(), $rules);
    
        if ($validator->fails()) {
            return ResponseHelper::error($validator->errors(), 'Validation Failed', 400);
        }
    
        $user = Auth::user();
    
        if (!$user) {
            return ResponseHelper::error('Unauthorized user',null, 401);
        }
    
        // Encrypt Aadhaar
        $aadhaarNumber = $request->AadharNumber;
    
        $payload = [
            "referenceKey" => $request->referenceKey,
            "latitude" => $request->latitude,
            "longitude" => $request->longitude,
            "externalRef" => time() . rand(100000, 999999),
            "dc" => $request->dc,
            "ci" => $request->ci,
            "hmac" => $request->hmac,
            "dpId" => $request->dpId,
            "mc" => $request->mc,
            "pidDataType" => $request->pidDataType,
            "rdsId" => $request->rdsId,
            "sessionKey" => $request->sessionKey,
            "mi" => $request->mi,
            "errCode" => $request->errCode ?? "0",
            "errInfo" => $request->errInfo ?? "Success",
            "fCount" => $request->fCount ?? "2",
            "fType" => $request->fType ?? "2",
            "iCount" => $request->iCount ?? "0",
            "iType" => $request->iType ?? 1,
            "pCount" => $request->pCount ?? 1,
            "pType" => $request->pType ?? "",
            "srno" => $request->srno ?? "",
            "sysid" => $request->sysid ?? "",
            "ts" => $request->ts,
            "pidData" => $request->pidData,
            "qScore" => $request->qScore ?? "",
            "nmPoints" => $request->nmPoints ?? "",
            "rdsVer" => $request->rdsVer,
            "outlet_id" => $user->instId,
            "AadharNumber"=>$aadhaarNumber
        ];
    
        $url = 'https://api.impsguru.com/prod/v1/aeps/merchantBiomatricKycIpay';
        $response = Impsguru::guruRequest($url, 'POST', json_encode($payload), '');
  
            $statusCode = $response['response']['statuscode'] ?? null;
            $message    = $response['response']['data']['status']?? 'Unknown';
            // return $message;
            $finalData  = $response['response']['data'] ?? [];
    
        if (!$response) {
            return ResponseHelper::error( 'No response received from UIDAI server. Please try later.',null, 403);
        }
    
        if (!isset($response['statuscode']) || $response['statuscode'] != 'TXN') {
            return ResponseHelper::error($message, $finalData, 403);
        }
    
        // Success
        $user->outlet_status = 1;
        $user->save();
    
        return ResponseHelper::success($message ,$finalData, 200);
    }

    public function balanceEnquiry(Request $request){
        $user = Auth::user();
         if($user->id==82){
            // Request log
            Log::info('aeps balance request', [
                'request' => $request->all()
            ]);
        }
        $dupCheck = Uuid::check($request->client_ref,'balanceEnquiry Aeps1',$request);
        if($dupCheck['code'] != '200'){
            return ResponseHelper::error('Duplicate Transaction',[],402);
        }
        $validator = AepsValidation::balanceEnquiry($request->all());
        if ($validator->fails()) {
            return ResponseHelper::error('Validation Failed', $validator->errors(), 400);
        }
        $ipayDetails = IpayDetail::where("user_id", $user->id)->first();
        if (!$ipayDetails) {
            return ResponseHelper::error('Please complete outlet registration', [], 403);
        }
        $data = $request->all();
        if (!is_null($ipayDetails->latitude) && !is_null($ipayDetails->longitude)) {
            $data['latitude'] = $ipayDetails->latitude;
            $data['longitude'] = $ipayDetails->longitude;
        } else{
            return ResponseHelper::error('Location Details Not Found', [], 403);
        }
        // // Verify MPIN
        // if (!OTP::verifyMpin($request->mpin)) {
        //     return ResponseHelper::error("Invalid MPIN", [], 403);
        // }
        if (!in_array($user->role, ["api", "dd", "ret"])) {
            return ResponseHelper::error('Action not Authorised', '', 403);
        }
        // if ($user->w1 < 100+$user->lien) {
        // return ResponseHelper::error('Insufficient W1 Balance', '', 403);
        // }
        $service = Service::find($request->operator);
        //return $service;
        if (!$service) {
            return ResponseHelper::error('Invalid Operator/Service', [], 400);
        }
        $comm = CommRule::calculateCommission($user, $service->name, 0);
        if(!$comm){
            return ResponseHelper::error('Commission Slab Not Configured', [], 400);
        }
        $comm = CommRule::finalCommission($comm, 0,$service);
        $aadhaarNumber = $data['AadhaarNumber'] ?? null;
        if (empty($aadhaarNumber)) {
            return ResponseHelper::error('AadharNumber is required', [], 400);
        }
        
        DB::beginTransaction();
        try {
            $txn = AepsService::createBalanceEnqueiry($user, $service, $request, $comm,0);
            $auditLog = AuditService::logRequest($user, $request, $service);
            DB::commit();
        } catch (\Exception $e) {
            DB::rollBack();
            return ResponseHelper::error('Transaction Init Failed', $e->getMessage(), 500);
        }
  
        $data['req_type'] = 'BALANCE';
        $data['outlet_id']=$user->instId;
        $data['txn_id']=$txn->txn_id;
        $data['user_agent'] = $request->header('User-Agent');  
        $data['ip'] = $request->ip();                        
        $encryptedData = AepsPayloadModel::encryptAadhaar($aadhaarNumber);
        $data['AadharNumber']=$aadhaarNumber;
        $payload = AepsPayloadModel::balanceEnquiryPayload($data, $encryptedData);
        $url='https://api.impsguru.com/prod/v1/aeps/balanceInquiry';
        $response = Impsguru::guruRequest($url, 'POST', $payload,'');
        DB::beginTransaction();
        try {
           $result= AepsResponseHandler::handleBalanceEnquiry($txn, $response["response"], $user, $service, $comm);
            DB::commit();
            return $result;
        } catch (\Exception $e) {
            DB::rollBack();
            return ResponseHelper::error('Transaction Update Failed', $e->getMessage(), 500);
        }
        return response()->json([
            'status'  => $status,
            'message' => $response['message'] ?? 'API responded',
            'data'    => $response
        ], 403);
    }
    public function cashWithdrawl(Request $request){
    
        $user = Auth::user();
         $dupCheck = Uuid::check($request->client_ref,'cashWithdrawl Aeps1',$request);
        if($dupCheck['code'] != '200'){
            return ResponseHelper::error('Duplicate Transaction',[],402);
        }
        $validator = AepsValidation::cashWithdrawl($request->all());
      
        if ($validator->fails()) {
            return ResponseHelper::error('Validation Failed', $validator->errors(), 400);
        }
       $ipayDetails = IpayDetail::where("user_id", $user->id)->first();
        if (!$ipayDetails) {
            return ResponseHelper::error('Please complete outlet registration', [], 403);
        }
    
        $data = $request->all();
        if (!is_null($ipayDetails->latitude) && !is_null($ipayDetails->longitude)) {
            $data['latitude'] = $ipayDetails->latitude;
            $data['longitude'] = $ipayDetails->longitude;
        } else{
            return ResponseHelper::error('Location Details Not Found', [], 403);
        }
    
        // // Verify MPIN
        // if (!OTP::verifyMpin($request->mpin)) {
        //     return ResponseHelper::error("Invalid MPIN", [], 403);
        // }
    
        // Role check
        if (!in_array($user->role, ["api", "dd", "ret"])) {
            return ResponseHelper::error('Action not Authorised', '', 403);
        }
        // if ($user->w1 ) {
        //     return ResponseHelper::error('Insufficient W1 Balance', '', 403);
        // }
    
        // Service & Commission
        $service = Service::find($request->operator);
        //return $service;
        if (!$service) {
            return ResponseHelper::error('Invalid Operator/Service', [], 400);
        }
        
        $comm = CommRule::calculateCommission($user, $service->name, $request->amount);
        if(!$comm){
          return ResponseHelper::error('Commission Slab Not Configured', '', 403);
        }
         $a_comm = AcommRule::calculateAdminCommission($service->name, $request->amount, $service->route);
        if(!$a_comm){
                return ResponseHelper::error('Admin Commission Slab Not Configured', '', 403);
        }
        $comm = CommRule::finalCommission($comm, $request->amount,$service);
        $a_comm = AcommRule::finalCommission($a_comm, $request->amount);
        $aadhaarNumber = $data['AadhaarNumber'] ?? null;
        if (empty($aadhaarNumber)) {
            return ResponseHelper::error('AadharNumber is required', [], 400);
        }
        
        DB::beginTransaction();
        try {
            $txn = AepsService::createCashWithdraw($user, $service, $request, $comm,$a_comm);
            $auditLog = AuditService::logRequest($user, $request, $service);
            DB::commit();
        } catch (\Exception $e) {
            DB::rollBack();
            return ResponseHelper::error('Transaction Init Failed', $e->getMessage(), 500);
        }
            $data['req_type'] = 'CASH';
            $data['outlet_id']=$user->instId;
            $data['txn_id']=$txn->txn_id;
            $data['user_agent'] = $request->header('User-Agent');  
            $data['ip'] = $request->ip();   
            $encryptedData = AepsPayloadModel::encryptAadhaar($aadhaarNumber);
            $payload = AepsPayloadModel::cashWithdrawPayload($data, $encryptedData);
            $url='https://api.impsguru.com/prod/v1/aeps/cashWithdrawl';
            $response = Impsguru::guruRequest($url, 'POST', $payload,'');
             //return $response;
        DB::beginTransaction();
        try {
           $result= AepsResponseHandler::handleCashWithdraw($txn, $response, $user, $service, $comm);
            DB::commit();
            return $result;
        } catch (\Exception $e) {
            DB::rollBack();
            return ResponseHelper::error('Transaction Update Failed', $e->getMessage(), 500);
        }
         return ResponseHelper::error($response['message'] ,$response, 403);
    }
    public function miniStatement(Request $request){
        $user = Auth::user();
        $dupCheck = Uuid::check($request->client_ref,'miniStatement Aeps1',$request);
        if($dupCheck['code'] != '200'){
            return ResponseHelper::error('Duplicate Transaction',[],402);
        }
        $validator = AepsValidation::balanceEnquiry($request->all());
      
        if ($validator->fails()) {
            return ResponseHelper::error('Validation Failed', $validator->errors(), 400);
        }
    
    
        $ipayDetails = IpayDetail::where("user_id", $user->id)->first();
        if (!$ipayDetails) {
            return ResponseHelper::error('Please complete outlet registration', [], 403);
        }
    
        $data = $request->all();
        if (!is_null($ipayDetails->latitude) && !is_null($ipayDetails->longitude)) {
            $data['latitude'] = $ipayDetails->latitude;
            $data['longitude'] = $ipayDetails->longitude;
        } else{
            return ResponseHelper::error('Location Details Not Found', [], 403);
        }
    
        // // Verify MPIN
        // if (!OTP::verifyMpin($request->mpin)) {
        //     return ResponseHelper::error("Invalid MPIN", [], 403);
        // }
    
        // Role check
        if (!in_array($user->role, ["api", "dd", "ret"])) {
            return ResponseHelper::error('Action not Authorised', '', 403);
        }
        // if ($user->w1 < 100+$user->lien*100) {
        // return ResponseHelper::error('Insufficient W1 Balance', '', 403);
        // }

    
        // Service & Commission
        $service = Service::find($request->operator);
        //return $service;
        if (!$service) {
            return ResponseHelper::error('Invalid Operator/Service', [], 400);
        }
        
        $comm = CommRule::calculateCommission($user, $service->name, 0);
        if(!$comm){
          return ResponseHelper::error('Commission Slab Not Configured', '', 403);
        }
        $comm = CommRule::finalCommission($comm, 0,$service);
    
        // Encrypt Aadhaar
        $aadhaarNumber = $data['AadhaarNumber'] ?? null;
        if (empty($aadhaarNumber)) {
            return ResponseHelper::error('AadharNumber is required', [], 400);
        }
        $data['req_type'] = 'STATEMENT';
           $data['order_id']=$user->instId;
            $data['outlet_id']=$user->instId;
            // $data['txn_id']=$txn->txn_id;
            $data['user_agent'] = $request->header('User-Agent');  
            $data['ip'] = $request->ip();  
        $encryptedData = AepsPayloadModel::encryptAadhaar($aadhaarNumber);
        $payload = AepsPayloadModel::balanceEnquiryPayload($data, $encryptedData);
        DB::beginTransaction();
        try {
            $txn = AepsService::createMiniStatement($user, $service, $request, $comm,0);
            $auditLog = AuditService::logRequest($user, $request, $service);
            DB::commit();
        } catch (\Exception $e) {
            DB::rollBack();
            return ResponseHelper::error('Transaction Init Failed', $e->getMessage(), 500);
        }
        try {
            $data['req_type'] = 'STATEMENT';
           $data['order_id']=$user->instId;
            $data['outlet_id']=$user->instId;
            $data['AadharNumber']=$aadhaarNumber;
            $data['txn_id']=$txn->txn_id;
            $data['user_agent'] = $request->header('User-Agent');  
            $data['ip'] = $request->ip();  
        $encryptedData = AepsPayloadModel::encryptAadhaar($aadhaarNumber);
        $payload = AepsPayloadModel::miniStatementPayload($data, $encryptedData);
         $url='https://api.impsguru.com/prod/v1/aeps/miniStatement';
              $response = Impsguru::guruRequest($url, 'POST', $payload,'');
            //   return $response;
        } catch (\Exception $e) {
            return ResponseHelper::error('Ipay Service Error', $e->getMessage(), 500);
        }
        DB::beginTransaction();
        try {
           $result= AepsResponseHandler::handleBalanceEnquiry($txn, $response['response'], $user, $service, $comm);
            DB::commit();
            return $result;
        } catch (\Exception $e) {
            DB::rollBack();
            return ResponseHelper::error('Transaction Update Failed', $response, 500);
        }
        return response()->json([
            'status'  => "FAILED",
            'message' => $response['message'] ?? 'API responded',
            'data'    => $response
        ], 403);
    }

}
