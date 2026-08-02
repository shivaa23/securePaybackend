<?php

namespace App\Modules\Aeps\Controllers;

use Laravel\Lumen\Routing\Controller as BaseController;
use App\Traits\ApiResponser;
use Illuminate\Http\Request;
use App\Modules\Aeps\Models\AepsTxn;
use App\Modules\Aeps\Models\Aeps2Validation;
use App\Models\User;
use App\Helper\ResponseHelper;
use App\Modules\Integration\Models\Fingpay;
use Validator;
use Auth;
use App\Modules\Aeps\Services\AepsResponseHandler;
use App\Modules\WalletLedger\Services\WalletService;
use App\Modules\AuditLog\Services\AuditService;
use App\Modules\Aeps\Services\AepsService; 
use App\Modules\Aeps\Models\AepsPayloadModel;
use App\Modules\Aeps\Models\AepsBank;
use App\Modules\Auth\Models\Otp;
use App\Modules\Service\Models\Service;
use App\Modules\Commission\Models\CommRule;
use Illuminate\Support\Facades\DB;
use App\Modules\Commission\Models\AcommRule;
use App\Modules\User\Models\FpayDetail;
use Illuminate\Support\Facades\Log;
class Aeps2Controller extends BaseController
{
    use ApiResponser;
    
    
    

    public function __construct()
    {
        $this->middleware('auth', ['except' => ['threeWayReconFp']]);
        $this->middleware('check.active', ['except' => ['threeWayReconFp']]);
    }
    // public function onboardingAeps2(Request $request)
    // {
    //     $validator = Aeps2Validation::onboardingAeps2($request->all());
    
    //     if ($validator->fails()) {
    //         return ResponseHelper::error($validator->errors(), 'Validation Failed', 400);
    //     }
    
    //     $data = $request->all();
    //     $data['ip'] = $request->ip();
    //     // $data['url'] = 'https://fingpayap.tapits.in/fpaepsweb/api/onboarding/merchant/php/creation/v2';
    //     // $data['method'] = 'POST';
 
    //     $payload = AepsPayloadModel::merchantOnboardingFp($data);
    //     // $data['post'] = json_encode($payload);
    
    //     // call encrypted Fingpay API
    //     $response = Fingpay::fingPay($payload);
    
    //     return $response;
    // }
    public function onboardingAeps2(Request $request) { 
        $validator = Aeps2Validation::onboardingAeps2($request->all());
        if ($validator->fails())
        { return ResponseHelper::error($validator->errors(),'Validation Failed', 400);
        }
        // $data = $request->all();
     
        $data = $request->all();
        $data['ip'] = $request->ip();
        // $data['url'] = 'https://fingpayap.tapits.in/fpaepsweb/api/onboarding/merchant/php/creation/v2';
        // $data['method'] = 'POST';
 
        $payload = AepsPayloadModel::merchantOnboardingFp($data);
        // $data['post'] = json_encode($payload);
    
        // call encrypted Fingpay API
        $response = Fingpay::fingPay($payload);
         $datanew=[
             "payload"=>json_encode($payload),
             "response"=>$response
             ];
        return $datanew;
        // return $response;
        if (!$response)
        { return ResponseHelper::error( 'No Response Received. UIDAI might be down, please try again later', [], 503 );
        } 
        if ($response['statusCode'] == 10000) 
        { return ResponseHelper::success($response['data']['remarks'], $datanew, 200);
        }elseif($response['statusCode'] == 10004){
            return ResponseHelper::error( $response['data']['remarks'],$datanew, 403); 
            
        } return ResponseHelper::error($response['data']['remarks'], $datanew, 403); }
    public function getStatesFp(){
        $curl = curl_init();
    
        curl_setopt_array($curl, [
            CURLOPT_URL => 'https://fingpayap.tapits.in/fpaepsweb/api/onboarding/getstates',
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_ENCODING => '',
            CURLOPT_MAXREDIRS => 10,
            CURLOPT_TIMEOUT => 0,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
            CURLOPT_CUSTOMREQUEST => 'GET',
        ]);
    
        $response = curl_exec($curl);
        $err = curl_error($curl);
    
        curl_close($curl);
    
        if ($err) {
            return ResponseHelper::error('Failed to fetch states list', $err, 500);
        }
    
        // RAW array decoded
        $data = json_decode($response, true);
    
        if (json_last_error() !== JSON_ERROR_NONE) {
            return ResponseHelper::error('Invalid JSON response', [], 500);
        }
    
        // Wrap array inside data
        return ResponseHelper::success('States list fetched successfully', 
            $data);
    }
    public function getBanksFp(){
        $curl = curl_init();
    
        curl_setopt_array($curl, array(
            CURLOPT_URL => 'https://fingpayap.tapits.in/fpaepsservice/api/bankdata/bank/details',
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_ENCODING => '',
            CURLOPT_MAXREDIRS => 10,
            CURLOPT_TIMEOUT => 0,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
            CURLOPT_CUSTOMREQUEST => 'GET',
        ));
    
        $response = curl_exec($curl);
        $err = curl_error($curl);
    
        curl_close($curl);
    
        if ($err) {
            return ResponseHelper::error('Failed to fetch bank list', $err, 500);
        }
    
        // Convert JSON to array
        $data = json_decode($response, true);
    
        if (json_last_error() !== JSON_ERROR_NONE) {
            return ResponseHelper::error('Invalid JSON response', [], 500);
        }
    
        return ResponseHelper::success('Bank list fetched successfully', $data);
    }
    public function onboardingekycFp(Request $request){
        $validator = Aeps2Validation::onboardingEkycFp($request->all());
    
        if ($validator->fails()) {
            return ResponseHelper::error('Validation Failed', $validator->errors(), 400);
        }
    
        $data = $request->all();
        $data['aadhaar'] = $request->AadhaarNumber;
        $data['ip'] = $request->ip();
        // $data['url'] = 'https://fpekyc.tapits.in/fpekyc/api/ekyc/merchant/php/sendotp';
        $data= AepsPayloadModel::merchantEkycFp($data); 
        // $data['method'] = 'POST';
    
        $response = Fingpay::fingPay($data);
        //  return $response;
        if (isset($response)) {
            switch ($response['statusCode']) {
                case 10000:
                       $data=[];
                       $data['primaryKeyId']=$response['data']['primaryKeyId'];
                        $data['encodeFPTxnId']=$response['data']['encodeFPTxnId'];
                    return ResponseHelper::success($response['data']['remarks'] ?? 'EKYC Successful',$data,200);
                
                case 10004:
                    return ResponseHelper::error(
                        $response['data'] ?? 'Operation Failed',
                        $response,
                        403
                    );
    
                default:
                    return ResponseHelper::error(
                        $response['data']['remarks'] ?? 'Operation Failed',
                        $response,
                        403
                    );
            }
        }
    
        return ResponseHelper::error('Server Error', [], 500);
    }
    public function aeps2Schema(Request $request){
       $schema = Aeps2Validation::getAeps2Schema($request->all());  
    return ResponseHelper::success("Schema fetched",$schema, 200);
    }
    public function validateOtpAeps2(Request $request){
        $validator = Aeps2Validation::validateOtpFp($request->all());
    
        if ($validator->fails()) {
            return ResponseHelper::error('Validation Failed', $validator->errors(), 400);
        }
    
        $data = $request->all();
        $data['aadhaar'] = $request->AadhaarNumber;
        // $data['ip'] = $request->ip();
        // $data['url'] = 'https://fpekyc.tapits.in/fpekyc/api/ekyc/merchant/php/validateotp';
         $payload= AepsPayloadModel::validateOtpFpApi($data); 
       
    
        $response = Fingpay::fingPay($payload);
        return $response;
        if (isset($response)) {
            switch ($response['statusCode']) {
                case 10000:
                          $data=[];
                       $data['primaryKeyId']=$response['data']['primaryKeyId'];
                        $data['encodeFPTxnId']=$response['data']['encodeFPTxnId'];
                    return ResponseHelper::success(
                        $response['data']['remarks'] ?? 'EKYC Successful',$data,200
                    );
                
                case 10004:
                    return ResponseHelper::error(
                        $response['data'] ?? 'Operation Failed',
                        $response,
                        403
                    );
    
                default:
                    return ResponseHelper::error(
                        $response['data']['remarks'] ?? 'Operation Failed',
                        $response,
                        403
                    );
            }
        }
    
        return ResponseHelper::error('Server Error', [], 500);
    }
    public function merchantBiometricKyc(Request $request){
        $user = Auth::user();
    
        // Validate request
        $validator = Aeps2Validation::merchantBiometricKyc($request->all());
        if ($validator->fails()) {
            return ResponseHelper::error('Validation Failed', $validator->errors(), 400);
        }
    
        $data = $request->all();
        $data['aadhaar'] = $request->adhaarNumber; // Correct field casing
        $data['ip'] = $request->ip();
        $data['method'] = 'POST';
        $data['url'] = 'https://fpekyc.tapits.in/fpekyc/api/ekyc/merchant/php/biometric';
    
        $payload = AepsPayloadModel::merchantBiomatricKyc($data);
        $response = Fingpay::fingPay($payload);
    
        if (!isset($response)) {
            return ResponseHelper::error('Server Error', [], 500);
        }
    
        // ------------ SUCCESS RESPONSE CHECK ------------
        if ($response['statusCode'] == 10000) {
    
            DB::beginTransaction();
            try {
    
                $fingId = "TRANS" . $user->id;
    
                // Update user
                $user->fingId = $fingId;
                $user->save();
    
                // Update/Create FpayDetails
                FpayDetail::updateOrCreate(
                    ['user_id' => $user->id],
                    [
                        'fpay_id'   => $fingId,
                        'fingId'    => $fingId,
                        'latitude'  => $request->latitude,
                        'longitude' => $request->longitude,
                        'kyc_res'   => $response,
                    ]
                );
    
                DB::commit();
    
                return ResponseHelper::success(
                    $response['data']['remarks'] ?? 'EKYC Successful',
                    $response['data'] ?? []
                );
    
            } catch (\Exception $e) {
    
                DB::rollBack();
    
                return ResponseHelper::error(
                    'Database Error: ' . $e->getMessage(),
                    [],
                    500
                );
            }
        }
    
        // ------------ FAILURE RESPONSE ------------
        return ResponseHelper::error(
            $response['data']['remarks'] ?? $response['message'] ?? 'Operation Failed',
            $response,
            403
        );
    }

//     public function balanceEnquiry2(Request $request){
//         // return "sdjhgskd";
//     $user = Auth::user();

//     // 1. Validation
//     $validator = Aeps2Validation::balanceInquiryFp($request->all());
//     if ($validator->fails()) {
//         return ResponseHelper::error('Validation Failed', $validator->errors(), 400);
//     }

//     // 2. Role Check
//     if (!in_array($user->role, ["api", "dd", "ret"])) {
//         return ResponseHelper::error('Action not Authorised', '', 403);
//     }

//     // 3. Operator Check
//     $service = Service::find($request->operator);
//     if (!$service) {
//         return ResponseHelper::error('Invalid Operator/Service', [], 400);
//     }

//     // 4. Commission
//     $comm = CommRule::calculateCommission($user, $service->name, 0);
//     if (!$comm) {
//         return ResponseHelper::error('Commission Slab Not Configured', [], 400);
//     }
//     $comm = CommRule::finalCommission($comm, 0, $service);

//     // 5. Transaction Start
//     DB::beginTransaction();
//     try {
//         $txn = AepsService::createBalanceEnqueiry2($user, $service, $request, $comm, 0);
//         AuditService::logRequest($user, $request, $service);
//         DB::commit();
//     } catch (\Exception $e) {
//         DB::rollBack();
//         return ResponseHelper::error('Transaction Init Failed', $e->getMessage(), 500);
//     }

//     // 6. Build Payload Input
//     $data = $request->all();
//     $data['req_type'] = 'BALANCE';
//     $data['outlet_id'] = $user->fingId;
//     $data['order_id'] = $txn->txn_id;
//     $data['user_agent'] = $request->header('User-Agent');
//     $data['ip'] = $request->ip();
//     $data['captureResponse'] = $request->captureResponse;
//     return $data;
//     $payload = AepsPayloadModel::balanceInquiryFp($data);
//   return $payload;
//     // 8. API Call
//     $response = Fingpay::fingPay($payload);
//     // return $response;
//     // 9. Handle Response
//     DB::beginTransaction();
//     try {
//         $result = AepsResponseHandler::handleBalanceEnquiry2($txn, $response,$user, $service, $comm);
//         DB::commit();
//         return $result;

//     } catch (\Exception $e) {
//         DB::rollBack();
//         return ResponseHelper::error('Transaction Update Failed', $e->getMessage(), 500);
//     }
// }
    public function balanceEnquiry2(Request $request)
    {
        $user = Auth::user();
        if($user->id==82){
            // Request log
            Log::info('aeps balance request', [
                'request' => $request->all()
            ]);
        }
        // 1. Validation
        $validator = Aeps2Validation::balanceInquiryFp($request->all());
        if ($validator->fails()) {
            return ResponseHelper::error('Validation Failed', $validator->errors(), 400);
        }
    
        // 2. Role Check
        if (!in_array($user->role, ["api", "dd", "ret"])) {
            return ResponseHelper::error('Action not Authorised', '', 403);
        }
    
        // 3. Operator Check
        $service = Service::find($request->operator);
        if (!$service) {
            return ResponseHelper::error('Invalid Operator/Service', [], 400);
        }
    
        // 4. Commission
        $comm = CommRule::calculateCommission($user, $service->name, 1);
        if (!$comm) {
            return ResponseHelper::error('Commission Slab Not Configured', '', 403);
        }
    
        $a_comm = AcommRule::calculateAdminCommission($service->name, 1, $service->route);
        if (!$a_comm) {
            return ResponseHelper::error('Admin Commission Slab Not Configured', '', 403);
        }
    
        $comm   = CommRule::finalCommission($comm, 1, $service);
        $a_comm = AcommRule::finalCommission($a_comm, 1);
    
        /** ---------------- TRANSACTION INIT ---------------- **/
        DB::beginTransaction();
        try {
            $txn = AepsService::createBalanceEnqueiry2(
                $user,
                $service,
                $request,
                $comm,
                0
            );
    
            AuditService::logRequest($user, $request, $service);
            DB::commit();
        } catch (\Exception $e) {
            DB::rollBack();
            return ResponseHelper::error('Transaction Init Failed', $e->getMessage(), 500);
        }
    
        /** ---------------- PAYLOAD BUILD ---------------- **/
        $data = $request->all();
        $data['req_type']        = 'BALANCE';
        $data['outlet_id']       = $user->fingId;
        $data['order_id']        = $txn->txn_id;
        $data['user_agent']      = $request->header('User-Agent');
        $data['ip']              = $request->ip();
        $data['captureResponse'] = $request->captureResponse ?? 0;
        // return $data;
    
        $payload = AepsPayloadModel::balanceInquiryFp($data);
        // return json_decode($payload['post'], true);
        /** ---------------- API CALL ---------------- **/
        $response = Fingpay::fingPay($payload);
    
        /** ---------------- RESPONSE HANDLE ---------------- **/
        DB::beginTransaction();
        try {
            $result = AepsResponseHandler::handleBalanceEnquiry2(
                $txn,
                $response,
                $user,
                $service,
                $comm
            );
    
            DB::commit();
            return $result;
        } catch (\Exception $e) {
            DB::rollBack();
            return ResponseHelper::error('Transaction Update Failed', $e->getMessage(), 500);
        }
    }
    public function cashWithdrawl(Request $request){
    
        $user = Auth::user();
        //  $dupCheck = Uuid::check($request->client_ref,'cashWithdrawl Aeps1',$request);
        // if($dupCheck['code'] != '200'){
        //     return ResponseHelper::error('Duplicate Transaction',[],402);
        // }
        $validator = Aeps2Validation::cashWithdrawlFp($request->all());
      
        if ($validator->fails()) {
            return ResponseHelper::error($validator->errors(),'Validation Failed', 400);
            
        }
         $fpayDetails = FpayDetail::where("user_id", $user->id)->first();
        if (!$fpayDetails) {
            return ResponseHelper::error('Please complete outlet registration', [], 403);
        }
    
        $data = $request->all();
        if (!is_null($fpayDetails->latitude) && !is_null($fpayDetails->longitude)) {
            $data['latitude'] = $fpayDetails->latitude;
            $data['longitude'] = $fpayDetails->longitude;
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
        DB::beginTransaction();
        try {
            $txn = AepsService::createCashWithdraw2($user, $service, $request, $comm,$a_comm);
            $auditLog = AuditService::logRequest($user, $request, $service);
            DB::commit();
        } catch (\Exception $e) {
            DB::rollBack();
            return ResponseHelper::error('Transaction Init Failed', $e->getMessage(), 500);
        }
            $data['operator'] = 'AEF';
            $data['req_type'] = 'CASH';
            $data['ip'] = $request->ip(); 
            $data['order_id']=$txn->txn_id;
             $data['captureResponse'] = $request->captureResponse;
            $payload = AepsPayloadModel::cashWithdrawlFp($data);
            // $url='https://api.impsguru.com/prod/v1/aeps/cashWithdrawl';
              $response = Fingpay::fingPay($payload);
            //  return $response;
        DB::beginTransaction();
        try {
           $result= AepsResponseHandler::handleCashWithdraw2($txn, $response, $user, $service, $comm);
            DB::commit();
            return $result;
        } catch (\Exception $e) {
            DB::rollBack();
            return ResponseHelper::error('Transaction Update Failed', $e->getMessage(), 500);
        }
         return ResponseHelper::error($response['message'] ,$response, 403);
    }
      public function addharPayFp(Request $request){
    
        $user = Auth::user();
        //  $dupCheck = Uuid::check($request->client_ref,'cashWithdrawl Aeps1',$request);
        // if($dupCheck['code'] != '200'){
        //     return ResponseHelper::error('Duplicate Transaction',[],402);
        // }
        $validator = Aeps2Validation::addharPayFp($request->all());
      
        if ($validator->fails()) {
            return ResponseHelper::error($validator->errors(),'Validation Failed', 400);
            
        }
         $fpayDetails = FpayDetail::where("user_id", $user->id)->first();
        //  return $fpayDetails;
        if (!$fpayDetails) {
            return ResponseHelper::error('Please complete outlet registration', [], 403);
        }
    
        $data = $request->all();
        if (!is_null($fpayDetails->latitude) && !is_null($fpayDetails->longitude)) {
            $data['latitude'] = $fpayDetails->latitude;
            $data['longitude'] = $fpayDetails->longitude;
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
        DB::beginTransaction();
        try {
            $txn = AepsService::createCashWithdraw2($user, $service, $request, $comm,$a_comm);
            $auditLog = AuditService::logRequest($user, $request, $service);
            DB::commit();
        } catch (\Exception $e) {
            DB::rollBack();
            return ResponseHelper::error('Transaction Init Failed', $e->getMessage(), 500);
        }
            $data['operator'] = 'AEF';
            $data['req_type'] = 'CASH';
            $data['ip'] = $request->ip(); 
            $data['order_id']=$txn->txn_id;
             $data['captureResponse'] = $request->captureResponse;
            //  return $data;
            $payload = AepsPayloadModel::adhaarPay($data);
            // $url='https://api.impsguru.com/prod/v1/aeps/cashWithdrawl';
              $response = Fingpay::fingPay($payload);
            //  return $response;
        DB::beginTransaction();
        try {
           $result= AepsResponseHandler::handleCashWithdraw2($txn, $response, $user, $service, $comm);
            DB::commit();
            return $result;
        } catch (\Exception $e) {
            DB::rollBack();
            return ResponseHelper::error('Transaction Update Failed', $e->getMessage(), 500);
        }
         return ResponseHelper::error($response['message'] ,$response, 403);
    }
    public function threeWayReconFp()
    {
        $data = AepsTxn::where('route', 'FINGPAY')
            ->where('txn_type', 'CASH WITHDRAWAL')
            ->whereDate('created_at', date('Y-m-d', strtotime('-1 day')))
            ->get();
    
        foreach ($data as $dt) {
    
            $apiResponse = json_decode($dt->api_response, true);
    
            if (!isset($apiResponse['data']['fpTransactionId'])) {
                continue;
            }
    
            // Prepare payload
            $payload = AepsPayloadModel::threeWayReconFp($dt);
    
            // Request log
            Log::info('threeWayReconFp request', [
                'txn_id'  => $dt->id,
                'payload' => $payload
            ]);
    
            // API call
            $response = Fingpay::threeWayReconFp($payload);
    
            // Response log
            Log::info('threeWayReconFp response', [
                'txn_id'  => $dt->id,
                'response'=> $response
            ]);
        }
    
        return response()->json([
            'status' => 'SUCCESS'
        ], 200);
    }
    public function miniStatement(Request $request){
        $user = Auth::user();
        // $dupCheck = Uuid::check($request->client_ref,'miniStatement Aeps2',$request);
        // if($dupCheck['code'] != '200'){
        //     return ResponseHelper::error('Duplicate Transaction',[],402);
        // }
        $validator = Aeps2Validation::miniStatementFp($request->all());
      
        if ($validator->fails()) {
            return ResponseHelper::error('Validation Failed', $validator->errors(), 400);
        }
    
    
         $fpayDetails = FpayDetail::where("user_id", $user->id)->first();
        // if (!$fpayDetails) {
        //     return ResponseHelper::error('Please complete outlet registration', [], 403);
        // }
    
        $data = $request->all();
        // if (!is_null($fpayDetails->latitude) && !is_null($fpayDetails->longitude)) {
        //     $data['latitude'] = $fpayDetails->latitude;
        //     $data['longitude'] = $fpayDetails->longitude;
        // } else{
        //     return ResponseHelper::error('Location Details Not Found', [], 403);
        // }
    
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
              return ResponseHelper::error('Commission slab not found', [], 400);
        }
        $comm = CommRule::finalCommission($comm, 0,$service);
    
        // Encrypt Aadhaar
        
        DB::beginTransaction();
        try {
            $txn = AepsService::createMiniStatement2($user, $service, $request, $comm,0);
            $auditLog = AuditService::logRequest($user, $request, $service);
            DB::commit();
        } catch (\Exception $e) {
            DB::rollBack();
            return ResponseHelper::error('Transaction Init Failed', $e->getMessage(), 500);
        }
     
           
            $data['AadharNumber']=$request->AadharNumber;
            $data['order_id']=$txn->txn_id;
            $data['user_agent'] = $request->header('User-Agent');  
            $data['ip'] = $request->ip();  
            $data['captureResponse'] = $request->captureResponse;
            $payload = AepsPayloadModel::miniStatementFp($data);
            // $url='https://api.impsguru.com/prod/v1/aeps/cashWithdrawl';
            $response = Fingpay::fingPay($payload);
            //   return $response;
        DB::beginTransaction();
        try {
           $result= AepsResponseHandler::handleBalanceEnquiry2($txn, $response, $user, $service, $comm);
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
     public function twoFaAeps2(Request $request){
        $user = Auth::user();
        $data = $request->all();
         $validator = Aeps2Validation::twoFaFp($request->all());
      
        if ($validator->fails()) {
            return ResponseHelper::error('Validation Failed', $validator->errors(), 400);
        }
        $data['captureResponse'] = $request->captureResponse;
        // return $data;
       $payload = AepsPayloadModel::twoFaFp($data);
        // return json_decode($payload['post'], true);

        $response = Fingpay::fingPay($payload);
        // return $response;
        if(isset($response)){
            if($response['statusCode'] == 10000){
                return response()->json([
                        'status'=>'SUCCESS',
                        'message'=> $response['message'], 
                        'data' => $response['data'],
                         'request'=>json_decode($payload['post'] ,true),
                ],200);
            }else{
                return response()->json([
                        'status'=>'FAILED', 
                        'message' => $response['message'],
                        'data' => $response,
                        'request'=>json_decode($payload['post'] ,true),
                ],403);
            }
        }else{
            return response()->json([
                    'status'=>'FAILED', 
                    'message' => 'Server Error',
                     'request'=>json_decode($payload['post'] ,true),
            ],500);
        }
    }

}
