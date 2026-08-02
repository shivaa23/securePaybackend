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
use App\Modules\Commission\Models\CommRule;
class AepsController extends BaseController
{
    use ApiResponser;

    public function __construct()
    {
        $this->middleware('auth', ['except' => []]);
        $this->middleware('check.active', ['except' => []]);
    }

    /**
     * Fetch AEPS Transactions
     */
    public function getAepsTxns(Request $request){
        $user = Auth::user();
        $query = AepsTxn::query();
        if (!in_array($user->role, ["sadm", "adm"])) {
            $userIds = User::getDescendantUserIds($user->id);
            (empty($userIds)) ? $query->where("user_id", $user->id) : $query->whereIn("user_id", $userIds);
        }
        if ($request->filled('status')) {
            $query->where('status', strtoupper($request->status));
        }
        if ($request->filled('user_id')) {
            $query->where('user_id', $request->user_id);
        }
        if ($request->filled('route')) {
            $query->where('route', $request->route);
        }
        if ($request->filled('aadhaar_number')) {
            $query->where('aadhaar_number', $request->aadhaar_number);
        }
        if ($request->filled('client_ref')) {
            $query->where('client_ref', $request->client_ref);
        }
        if ($request->filled('txn_id')) {
            $query->where('txn_id', $request->txn_id);
        }
        if ($request->filled('from_date') && $request->filled('to_date')) {
            $query->whereDate('created_at', '>=', $request->from_date)
                  ->whereDate('created_at', '<=', $request->to_date);
        }
        if ($request->input('export') == 1) {
            $txns = $query->latest()->get();
            $txns->transform(function ($txn) {
                $txn->establishment = $txn->user->establishment ?? null;
                
                unset($txn->user);
                return $txn;
            });
            return ResponseHelper::success('Transactions fetched', $txns, 200);
        }
        $paginate = $request->input('paginate', 15);
        $txns = $query->latest()->paginate($paginate);
        $txns->getCollection()->transform(function ($txn) {
            $txn->establishment = $txn->user->establishment ??null;
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
    public function initiateSignupAeps(Request $request)
    {
        $validator = AepsValidation::initiateSignupIpay($request->all());
        if ($validator->fails()) {
            return ResponseHelper::error('Validation Failed',$validator->errors(), 400);
        }

        // Aadhaar Encryption
        $ivlen = openssl_cipher_iv_length('aes-256-cbc');
        $iv = openssl_random_pseudo_bytes($ivlen);
        $ciphertext = openssl_encrypt(
            $request->aadhaar,
            'aes-256-cbc',
            env("aepsOutletKey"),
            OPENSSL_RAW_DATA,
            $iv
        );
        $encryptedData = base64_encode($iv . $ciphertext);

        $data = $request->all();
        $data['aadhaar'] = $encryptedData;
        $data['ip'] = $request->ip();
        $data['url'] = 'https://api.instantpay.in/user/outlet/signup/initiate';
        $data['payload'] = json_encode([
            "mobile"        => $data['mobile'],
            "pan"           => $data['pan'],
            "email"         => $data['email'],
            "bankAccountNo" => $data['bankAccountNo'],
            "bankIfsc"      => $data['bankIfsc'],
            "aadhaar"       => $data['aadhaar'],
            "latitude"      => $data['latitude'],
            "longitude"     => $data['longitude'],
            "consent"       => "Y"
        ]);
        $data['method'] = 'POST';

        $response = Ipay::Aeps($data);

        if (!$response) {
            return ResponseHelper::error(
                'No Response Received. UIDAI might be down, please try again later',
                [],
                503
            );
        }

        if ($response['statuscode'] === 'TXN') {
            $res = [
                'otpReferenceID' => $response['data']['otpReferenceID'],
                'hash'           => $response['data']['hash'],
            ];
            return ResponseHelper::success('OTP Sent', $res, 200);
        }

        return ResponseHelper::error($response['status'], [], 403);
    }

    public function validateSignupAeps(Request $request)
    {
        $user = Auth::user();
    
        $validator = AepsValidation::validateSignupIpay($request->all());
        if ($validator->fails()) {
            return ResponseHelper::error('Validation Failed', $validator->errors(), 400);
        }
    
        $data = $request->all();
        $hash = $data['hash2'] ?? $data['hash'];
    
        $data['ip'] = $request->ip();
        $data['url'] = 'https://api.instantpay.in/user/outlet/signup/validate';
        $data['payload'] = json_encode([
            "otpReferenceID" => $data['otpReferenceID'],
            "otp"            => $data['otp'],
            "hash"           => $hash
        ]);
        $data['method'] = 'POST';
    
        $response = Ipay::Aeps($data);
    
        AuditLog::create([
            'user_id'      => $user->id,
            'role'         => $user->role,
            'action'       => 'Validate Aeps Outlet',
            'service_name' => 'auth',
            'request_data' => json_encode($request->all()),
            'response_data'=> json_encode($response),
            'ip_address'   => $request->ip(),
            'user_agent'   => $request->header('User-Agent'),
            'status'       => 'Success'
        ]);
    
        if (!$response) {
            return ResponseHelper::error(
                'No Response Received. UIDAI might be down, please try again later',
                [],
                503
            );
        }
    
        if ($response['statuscode'] !== 'TXN') {
            return ResponseHelper::error($response['status'], [], 403);
        }
    
        $oldUser = User::where('instId', $response['data']['outletId'])->first();
        if ($oldUser) {
            return ResponseHelper::error('User with this KYC already exists, try another KYC', [], 409);
        }
        $user->instId = $response['data']['outletId'];
        $user->save();
        IpayDetail::create([
            'ipay_id'   => $response['data']['outletId'],
            'user_id'   => $user->id,
            'instid'    => $response['data']['outletId'],
            'latitude'  => $request->latitude,
            'longitude' => $request->longitude,
            'kyc_res' => $response,
        ]);
    
        return ResponseHelper::success('User Verified Successfully', [], 200);
    }

    public function getInitiateSchema()
    {
        $schema=AepsValidation::getInitiateSignupIpaySchema();
        return ResponseHelper::success('AePS Initiate Signup Schema', $schema ,200);
    }
   
    public function outletLoginStatus(Request $request)
    {
        try {
            $user = Auth::user();
    
            $data['ip']        = $request->ip();
            $data['outlet_id'] = $user->instId;
            $data['url']       = 'https://api.instantpay.in/fi/aeps/outletLoginStatus';
            $data['payload']   = '';
            $data['method']    = 'POST';
    
            $response = Ipay::service($data);
    
            // Log the request/response
            AuditLog::create([
                'user_id'      => $user->id,
                'role'         => $user->role,
                'action'       => 'AEPS Outlet login status',
                'service_name' => 'auth',
                'request_data' => json_encode($request->all()),
                'response_data'=> json_encode($response),
                'ip_address'   => $request->ip(),
                'user_agent'   => $request->header('User-Agent'),
                'status'       => isset($response['statuscode']) && $response['statuscode'] === 'TXN' ? 'Success' : 'Failed'
            ]);
    
            if (!$response) {
                return ResponseHelper::error('Service Provider Error', [], 403);
            }
    
            if (isset($response['statuscode']) && $response['statuscode'] === 'TXN') {
                return ResponseHelper::success('SUCCESS', [
                    'message' => $response['actcode']
                ], 200);
            }
    
            return ResponseHelper::error($response['status'] ?? 'Unknown Error', [], 403);
    
        } catch (\Exception $e) {
            return ResponseHelper::error('Something went wrong', [
                'exception' => $e->getMessage()
            ], 500);
        }
    }
    public function outletLogin(Request $request)
    {
    
        $user = Auth::user();
       
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
        } elseif (!empty($request->latitude) && strpos($request->longitude, ',') !== false) {
            [$lat, $lon] = explode(',', $user->onb_location);
            $data['latitude'] = $request->latitude;
            $data['longitude'] = $request->longitude;
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
        if ($user->w1 < ($request->amount * 100)) {
            return ResponseHelper::error('Insufficient W1 Balance', '', 403);
        }
    
        // Service & Commission
        $service = Service::find($request->operator);
        //return $service;
        if (!$service) {
            return ResponseHelper::error('Invalid Operator/Service', [], 400);
        }
      
        $comm = CommRule::calculateCommission($user, $service->name, $amount);
        $comm = CommRule::finalCommission($comm, $amount,$service);
    
        // Encrypt Aadhaar
        $aadhaarNumber = $data['AadharNumber'] ?? null;
        if (empty($aadhaarNumber)) {
            return ResponseHelper::error('AadharNumber is required', [], 400);
        }
        $encryptedData = AepsPayloadModel::encryptAadhaar($aadhaarNumber);
        $payload = AepsPayloadModel::buildPayload($data, $encryptedData);
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
        try {
            $response = Ipay::service([
                'payload' => $payload,
                'url'     => 'https://api.instantpay.in/fi/aeps/outletLogin',
                'method'  => 'POST',
                'ip'=>$request->ip()
            ]);
            // return $response;
        } catch (\Exception $e) {
            return ResponseHelper::error('Ipay Service Error', $e->getMessage(), 500);
        }
        DB::beginTransaction();
        try {
           $result= AepsResponseHandler::handleVerification($txn, $response, $user, $service, $comm);
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
        ], $status === 'SUCCESS' ? 200 : 403);
    }
    public function balanceEnquiry(Request $request)
    {
    
        $user = Auth::user();
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
        } elseif (!empty($request->latitude) && strpos($request->longitude, ',') !== false) {
            [$lat, $lon] = explode(',', $user->onb_location);
            $data['latitude'] = $request->latitude;
            $data['longitude'] = $request->longitude;
        }
    
        // // Verify MPIN
        // if (!OTP::verifyMpin($request->mpin)) {
        //     return ResponseHelper::error("Invalid MPIN", [], 403);
        // }
    
        // Role check
        if (!in_array($user->role, ["api", "dd", "ret"])) {
            return ResponseHelper::error('Action not Authorised', '', 403);
        }
        if ($user->w1 < 100) {
        return ResponseHelper::error('Insufficient W1 Balance', '', 403);
        }

    
        // Service & Commission
        $service = Service::find($request->operator);
        //return $service;
        if (!$service) {
            return ResponseHelper::error('Invalid Operator/Service', [], 400);
        }
        
        $comm = CommRule::calculateCommission($user, $service->name, 0);
        $comm = CommRule::finalCommission($comm, 0,$service);
    
        // Encrypt Aadhaar
        $aadhaarNumber = $data['AadhaarNumber'] ?? null;
        if (empty($aadhaarNumber)) {
            return ResponseHelper::error('AadharNumber is required', [], 400);
        }
        $data['req_type'] = 'BALANCE';
        $data['outlet_id']=$user->instId;
        $encryptedData = AepsPayloadModel::encryptAadhaar($aadhaarNumber);
        $payload = AepsPayloadModel::balanceEnquiryPayload($data, $encryptedData);
        DB::beginTransaction();
        try {
            $txn = AepsService::create($user, $service, $request, $comm,0);
            $auditLog = AuditService::logRequest($user, $request, $service);
            DB::commit();
        } catch (\Exception $e) {
            DB::rollBack();
            return ResponseHelper::error('Transaction Init Failed', $e->getMessage(), 500);
        }
        try {
            $response = Ipay::service([
                'payload' => $payload,
                'url'     => 'https://api.instantpay.in/fi/aeps/balanceInquiry',
                'method'  => 'POST',
                'ip'=>$request->ip()
            ]);
        } catch (\Exception $e) {
            return ResponseHelper::error('Ipay Service Error', $e->getMessage(), 500);
        }
        DB::beginTransaction();
        try {
           $result= AepsResponseHandler::handleBalanceEnquiry($txn, $response, $user, $service, $comm);
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
        ], $status === 'SUCCESS' ? 200 : 403);
    }
    public function cashWithdrawl(Request $request)
    {
    
        $user = Auth::user();
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
        } elseif (!empty($request->latitude) && strpos($request->longitude, ',') !== false) {
            [$lat, $lon] = explode(',', $user->onb_location);
            $data['latitude'] = $request->latitude;
            $data['longitude'] = $request->longitude;
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
        $comm = CommRule::finalCommission($comm, $request->amount,$service);
    //   return $comm;
        // Encrypt Aadhaar
        $aadhaarNumber = $data['AadhaarNumber'] ?? null;
        if (empty($aadhaarNumber)) {
            return ResponseHelper::error('AadharNumber is required', [], 400);
        }
        
        DB::beginTransaction();
        try {
            $txn = AepsService::createCashWithdraw($user, $service, $request, $comm,$request->amount);
            $auditLog = AuditService::logRequest($user, $request, $service);
            DB::commit();
        } catch (\Exception $e) {
            DB::rollBack();
            return ResponseHelper::error('Transaction Init Failed', $e->getMessage(), 500);
        }
        try {
            $data['req_type'] = 'CASH';
            $data['order_id']=$txn->txn_id;
            $encryptedData = AepsPayloadModel::encryptAadhaar($aadhaarNumber);
            $payload = AepsPayloadModel::cashWithdrawPayload($data, $encryptedData);
            $response = Ipay::service([
                'payload' => $payload,
                'url'     => 'https://api.instantpay.in/fi/aeps/cashWithdrawal',
                'method'  => 'POST',
                'ip'=>$request->ip(),
            ]);
            // return $response;
        } catch (\Exception $e) {
            return ResponseHelper::error('Ipay Service Error', $e->getMessage(), 500);
        }
        DB::beginTransaction();
        try {
           $result= AepsResponseHandler::handleCashWithdraw($txn, $response, $user, $service, $comm);
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
        ], $status === 'SUCCESS' ? 200 : 403);
    }
    public function miniStatement(Request $request)
    {
    
        $user = Auth::user();
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
        } elseif (!empty($request->latitude) && strpos($request->longitude, ',') !== false) {
            [$lat, $lon] = explode(',', $user->onb_location);
            $data['latitude'] = $request->latitude;
            $data['longitude'] = $request->longitude;
        }
    
        // // Verify MPIN
        // if (!OTP::verifyMpin($request->mpin)) {
        //     return ResponseHelper::error("Invalid MPIN", [], 403);
        // }
    
        // Role check
        if (!in_array($user->role, ["api", "dd", "ret"])) {
            return ResponseHelper::error('Action not Authorised', '', 403);
        }
        if ($user->w1 < 100) {
        return ResponseHelper::error('Insufficient W1 Balance', '', 403);
        }

    
        // Service & Commission
        $service = Service::find($request->operator);
        //return $service;
        if (!$service) {
            return ResponseHelper::error('Invalid Operator/Service', [], 400);
        }
        
        $comm = CommRule::calculateCommission($user, $service->name, 0);
        $comm = CommRule::finalCommission($comm, 0,$service);
    
        // Encrypt Aadhaar
        $aadhaarNumber = $data['AadhaarNumber'] ?? null;
        if (empty($aadhaarNumber)) {
            return ResponseHelper::error('AadharNumber is required', [], 400);
        }
        $data['req_type'] = 'STATEMENT';
        $data['outlet_id']=$user->instId;
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
            $response = Ipay::service([
                'payload' => $payload,
                'url'     => 'https://api.instantpay.in/fi/aeps/miniStatement',
                'method'  => 'POST',
                'ip'=>$request->ip()
            ]);
        } catch (\Exception $e) {
            return ResponseHelper::error('Ipay Service Error', $e->getMessage(), 500);
        }
        DB::beginTransaction();
        try {
           $result= AepsResponseHandler::handleBalanceEnquiry($txn, $response, $user, $service, $comm);
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
        ], $status === 'SUCCESS' ? 200 : 403);
    }
    public function miniStatementold(Request $request){
        // $msg = '[{"date":"02\/02","txnType":"D","amount":"100.00","narration":"POS\/W\/236782"},{"date":"02\/02","txnType":"D","amount":"94.00","narration":"MAT\/F\/655992"},{"date":"02\/02","txnType":"D","amount":"100.00","narration":"MAT\/F\/339176"},{"date":"01\/02","txnType":"C","amount":"47.00","narration":"MAT\/D\/019873"},{"date":"31\/01","txnType":"D","amount":"2217.90","narration":"MAT\/W\/104123"},{"date":"30\/01","txnType":"D","amount":"300.00","narration":"MAT\/F\/595055"},{"date":"30\/01","txnType":"C","amount":"2100.00","narration":"MAT\/D\/997689"},{"date":"28\/01","txnType":"D","amount":"200.00","narration":"MAT\/F\/719595"},{"date":"26\/01","txnType":"C","amount":"1500.00","narration":"MAT\/D\/422588"}]';
        // return response()->json(['status'=>'SUCCESS', 
        //                                         'message' => 'Transaction Successful',
        //                                             'data'=> $msg],200);
        $rules = array('number' => 'required|numeric|digits:10',
                'AadharNumber'=> 'required|numeric|digits:12',
                'BankName'=>'required|string',
                'bank_iin'=>'required',
                'pidDataType'=>'required',
                'pidData'=>'required',
                'ci'=> 'required',
                'dc'=>'required',
                'dpId'=>'required',
                'fCount'=>'required',
                'hmac'=>'required',
                'mc'=>'required',
                'mi'=>'required',
                'nmPoints'=>'required',
                'qScore'=>'required',
                'rdsId' =>'required',
                'rdsVer' =>'required',
                'sessionKey'=>'required',
                'srno'=>'required',
                'pf'=>'required',
                'latitude'=>'required',
                'longitude'=>'required');
        $validator = Validator::make($request->all(), $rules);
        if ($validator->fails()) {
            return response()->json(['status' => 'FAILED',
                                        'message' => $validator->getMessageBag()->toArray()],400);
        }
        $user = Auth::user();
        if($user->status != 1){
            return response()->json(['status' => 'FAILED',
                                        'message' => 'Your Account Has Been Suspended Kindly Contact Administrator'],403);
        }
        $data = $request->all();
        if($user->onb_location != null){
            $location = explode(',',$user->onb_location);
            $data['latitude'] = $location[0];
            $data['longitude'] = $location[1];
        }
        $data['operator'] = 'AES';
        $data['req_type'] = 'STATEMENT';
        $data['ip'] = $request->ip();
        $response = Transaction::newTransaction($data);
        
        if($response['code'] == 200) {
            $txn = Transaction::find($response['data']['id']);
            $data['outlet_id'] = $user->instId;
            $data['user_agent'] = $_SERVER['HTTP_USER_AGENT'];
            $data['order_id'] = $txn->order_id;
            $data['created_at'] = $txn->created_at;
            $response = InstantPay::miniStatement($data);
            $log = ['request'=>$data, 'user_id'=>Auth::user()->id, 'fileName' =>'aeps','response'=>$response];
		    Logs::logs($log);
            $ad = User::find($user->parent);
            if(isset($response)){
                if($response['statuscode'] == 'TXN'){
                    $user->increment('w2', 50);
                    $txn->update(['op_id'=>$response['data']['operatorId'],
                                    'ben_name' => $response['data']['bankAccountBalance'],
                                    'operator' => 'AePS Mini Statement',
                                    'w1' => $user->w1/100,
                                    'w2' => $user->w2/100,
                                    'a_comm' => 0.5,
                                    'ad_closing' => isset($ad) ? $ad->w1/100 : 0,
                                    'api_response'=>$response,
                                    'status'=>'SUCCESS']);
                    $repPost = array('amount'=>1,'service'=>'statement');
					Report::createReport($repPost);
                    $user->ipay_auth_time = null;
                    $user->save();
                    return response()->json(['status'=>'SUCCESS', 
                                            'message' => $response['status'],
                                            'data'=>$response['data']['miniStatement'],
                                            'bankAccountBalance'=> $response['data']['bankAccountBalance']],200);
                }elseif($response['statuscode'] == 'TUP'){
                    $txn->update(['op_id'=>$response['status'],
                                    'operator' => 'AePS Mini Statement',
                                    'w1' => $user->w1/100,
                                    'w2' => $user->w2/100,
                                    'ad_closing' => (isset($ad)) ? $ad->w1/100 : 0,
                                    'api_response'=>$response]);
                    return response()->json(['status'=>'PENDING', 
                                                'message' => $response['status']],403);
                }else{
                    $txn->update(['op_id'=>$response['status'],
                                    'operator' => 'AePS Mini Statement',
                                    'w1' => $user->w1/100,
                                    'w2' => $user->w2/100,
                                    'ad_closing' => (isset($ad)) ? $ad->w1/100 : 0,
                                    'api_response'=>$response,
                                    'status'=>'FAILED']);
                    return response()->json(['status'=>'FAILED', 
                                                'message' => $response['status']],403);
                }
            }else{
                $txn->update(['op_id'=>'No Response',
                        'operator' => 'AePS Mini Statement',
                        'w1' => $user->w1/100,
                        'w2' => $user->w2/100,
                        'ad_closing' => (isset($ad)) ? $ad->w1/100 : 0]);
                return response()->json(['status'=>'PENDING', 
                                            'message' => 'Something Went Wrong'],403);
            }
        }else{
            return response()->json(['status'=>'PENDING', 
                                        'message' => 'Something Went Wrong'],403);
        }
    }
    

}
