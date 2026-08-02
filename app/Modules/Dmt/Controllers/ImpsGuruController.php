<?php

namespace App\Modules\Dmt\Controllers;

use Laravel\Lumen\Routing\Controller as BaseController;
use App\Traits\ApiResponser;
use Illuminate\Support\Facades\Validator;
use Illuminate\Http\Request;
use App\Modules\Dmt\Models\DmtTxn;
use App\Modules\Dmt\Models\DmtBeneficiarie;
use App\Modules\Dmt\Models\DmtValidation;
use App\Modules\Commission\Models\CommRule;
use App\Modules\Commission\Models\AcommRule;
use App\Modules\Service\Models\Service;
use Auth;
use App\Helper\ResponseHelper;
use App\Modules\Integration\Models\Impsguru;
use App\Modules\Integration\Models\Enkash;
use App\Modules\Dmt\Services\DmtService; 
use Illuminate\Support\Str;
use Illuminate\Support\Facades\DB;
use App\Modules\WalletLedger\Services\WalletService;
use App\Modules\AuditLog\Services\AuditService;
use App\Modules\Dmt\Services\DMTResponseHandler;
use App\Modules\Auth\Models\Otp;
use Illuminate\Support\Facades\Date;
use App\Modules\Misc\Models\Uuid;
define('ENCKEY','b55961e2b5bf1441b55961e2b5bf1441');
class ImpsGuruController extends BaseController
{
    use ApiResponser;

    public function __construct()
    {
        $this->middleware('auth', ['except' => ['']]);
        $this->middleware('check.active', ['except' => '']);
         $this->middleware('check.service.time',['except' => '']);

    }
    
    public function getBanks(Request $request) {
            $data['ip'] = $request->ip();
            $outlet_id = Auth::user()->instId ?? 243829;
            $url = 'https://api.impsguru.com/prod/v1/dmt/getBanks';
            $payload = '{"outlet_id":"'.$outlet_id.'"}';
            $method = 'POST';
            $response = Impsguru::guruRequest($url, $method, $payload, 'GETBANKS');
        
            if(isset($response)) {
                if($response['statuscode'] == 'TXN') {
        
                    // Remove duplicate banks based on 'name'
                    $uniqueBanks = [];
                    $seen = [];
                    foreach($response['data'] as $bank) {
                        if(!in_array($bank['name'], $seen)) {
                            $seen[] = $bank['name'];
                            $uniqueBanks[] = $bank;
                        }
                    }
        
                    // Sort banks by name ascending
                    usort($uniqueBanks, function($a, $b) {
                        return strcmp($a['name'], $b['name']);
                    });
        
                    return ResponseHelper::success('Banks Fetched Successfully', $uniqueBanks, 200);
                } else {
                    return ResponseHelper::error('Service Provider Down', '', 403);
                }
            }
        }
 	public function getRegisterRemitterSchema(Request $request){
          $schema = DmtValidation::getRegisterRemitterSchema($request);
           return ResponseHelper::success('schema fetched', $schema, 200);
     }
	public function remitterStatus(Request $request){
    	$validator = DmtValidation::remitterStatus($request->all());
        if ($validator->fails()) {
            return ResponseHelper::error('Validation Failed', $validator->errors(), 422);
        }
    
        $data['ip'] = $request->ip();
        $outlet_id = Auth::user()->instId ?? 243829;
        $url = 'https://api.impsguru.com/prod/v1/dmt/getRemitter';
        $payload = '{"outlet_id":"'.$outlet_id.'",
                    "mobileNumber":"'.$request->mobile_number.'"
        }';
        $method = 'POST';
        $response = Impsguru::guruRequest($url,$method,$payload,'REMITTER');
        //return $response;
        if (!isset($response)) {
            return ResponseHelper::error('No response from API', [], 500);
        }
    
        $statusCode = $response['statuscode'] ?? null;
        $beneficiaries = $response['data']['beneficiaries'] ?? [];
        if ($statusCode === 'TXN' ) {
            foreach ($beneficiaries as $bn) {
                dispatch(new \App\Jobs\ProcessBeneficiary($request->mobile_number, $bn));
            }
            $beneData = DmtBeneficiarie::where('sender',$request->mobile_number)->where('pipe','DMT1')->get();
            $response['data']['beneficiaries'] = $beneData;
            return ResponseHelper::success('Remitter Found', $response['data'], 200);
        }
        switch ($statusCode) {
            case 'RNF':
                return ResponseHelper::success($response['message'], $response['data'], 200);
            case 'OTP':
                return ResponseHelper::success($response['message'], $response['data'], 200);
            case 'ERR':
            default:
                return ResponseHelper::error($response['message'] ?? 'Unknown Error', $response['data'] ?? [], 403);
        }
    }
	public function registerRemitter(Request $request){
		$validator = DmtValidation::registerRemitter($request->all());
        if ($validator->fails()) {
            return ResponseHelper::error('Validation Failed', $validator->errors(), 422);
        }
		$data = $request->all();
		$aadhaarNumber = $data['aadhaar_number'];
		$outlet_id=Auth::User()->instId;
        $encryptionKey = ENCKEY;
        $ivlen = openssl_cipher_iv_length('aes-256-cbc');
        $iv = openssl_random_pseudo_bytes($ivlen);
        $ciphertext = openssl_encrypt($aadhaarNumber,'aes-256-cbc', $encryptionKey, OPENSSL_RAW_DATA, $iv);
        $encryptedData = base64_encode($iv . $ciphertext);
		$data['ip'] = $request->ip();
        $data['url'] = 'https://api.impsguru.com/prod/v1/dmt/registerRemitter';
          $data['payload'] = '{
            "mobileNumber": "'.$data['mobile_number'].'",
            "encryptedAadhaar" : "'.$encryptedData.'",
            "outlet_id" : "'.$outlet_id.'",
            "aadhaar_number" : "'.$aadhaarNumber.'",
            "referenceKey" : "'.$data['reference_key'].'"
        }';

        $data['method'] = 'POST';
        //$data['outlet_id'] = Auth::user()->instId;
        $response = Impsguru::guruRequest( $data['url'] ,$data['method'],$data['payload'] ,'REGISTERREMITTER');
        // return $response;
        if(isset($response)){
            if($response['statuscode'] == 'TXN'){
                return ResponseHelper::success($response['message'], $response['data'], 200);
            }elseif($response['statuscode'] == 'OTP'){
                return ResponseHelper::success($response['message'], $response['data'], 200);
            }elseif($response['statuscode'] == 'ERR'){
                return ResponseHelper::error($response['message'], $response['data'], 403);
            }else{
                return ResponseHelper::error($response['message'], $response['data'], 403);
            }
        }
        return ResponseHelper::error('No Response From Bank', '', 403);
	}
    public function validateRemitter(Request $request){
        $validator = DmtValidation::validateRemitter($request->all());
        if ($validator->fails()) {
            return ResponseHelper::error('Validation Failed', $validator->errors(), 422);
        }
    
        $data = $request->all();
        $outlet_id = Auth::user()->instId;
    
        $payload = [
            "mobileNumber" => $data['rem_number'],
            "otp"          => $data['otp'],
            "outlet_id"    => $outlet_id,
            "referenceKey" => $data['reference_key']
        ];
    
        $url = 'https://api.impsguru.com/prod/v1/dmt/validateRemitter';
        $method = 'POST';
    
        $response = Impsguru::guruRequest($url, $method, json_encode($payload), 'VALIDATEREMITTER');
    // return $response;
        if (!isset($response)) {
            return ResponseHelper::error('No Response From Bank', '', 403);
        }
    
        if (in_array($response['statuscode'], ['TXN', 'KYC'])) {
            return ResponseHelper::success($response['message'], $response['data'], 200);
        }
    
        return ResponseHelper::error($response['status'], [], 403);
    }
    public function kycRemitter(Request $request){
        $user = Auth::user();
        $validator = DmtValidation::kycRemitter($request->all());
        if ($validator->fails()) {
            return ResponseHelper::error('Validation error', $validator->errors(), 422);
        }
    
        if (!in_array($user->role, ["api", "dd", "ret"])) {
            return ResponseHelper::error('Action not Authorised', '', 403);
        }
        //   $dupCheck = Uuid::check($request->client_ref,'kycRemitter1',$request);
        // if($dupCheck['code'] != '200'){
        //     return ResponseHelper::error('Duplicate Transaction',[],402);
        // }

        $amount=1;
    
        $service = Service::find($request->operator);
        $comm = CommRule::calculateCommission($user, $service->name, $amount);
        if(!$comm){
            return ResponseHelper::error('Commission Rule Not Configured', [], 403);
        }
        if (in_array($user->role, ["api", "dd", "ret"])) {
            $comm = CommRule::finalCommission($comm, $amount, $service);
        }
             $requiredBalance = ($amount + $user->lien + ($comm['ret'] ?? $comm['dd'])) * 100;
         if ($user->w1 < $requiredBalance) {
            return ResponseHelper::error('Insufficient W1 Balance', [], 403);
        }
    
        DB::beginTransaction();
        try {
            $dmtTxn = DmtService::createKyc($user, $service, $request, $comm);
            WalletService::debit($user, $amount, $dmtTxn, $service);
            $auditLog = AuditService::logRequest($user, $request, $service);
        } catch (\Exception $e) {
            DB::rollBack();
            return ResponseHelper::error('Transaction Init Failed', $e->getMessage(), 500);
        }
            $data = $request->all();
            $data['amount'] = 0;
            $data['type'] = 'REMITTER';
            $data['ip'] = $request->ip();
            $captureType = $request->captureType ?? "FINGER";
            $ts = $data['ts'] ?? "";
    
            // Include outlet_id
            $outlet_id = $user->instId;
    
            $url = 'https://api.impsguru.com/prod/v1/dmt/remitterKyc';
            $payload = [
                "mobileNumber"  => $data['mobile_number'],
                "referenceKey"  => $data['reference_key'],
                "latitude"      => $data['latitude'],
                "longitude"     => $data['longitude'],
                 "operatorCode"     => "27",
                  "ip"     => $request->ip(),
                  "client_id"=>$dmtTxn->txn_id,
                "captureType"   => $captureType,
                "externalRef"   => $dmtTxn->txn_id,
                "consentTaken"  => "Y",
                "outlet_id"     => $outlet_id,
                // "biometricData" => [
                    "ci"     => $data['ci'],
                    "hmac"   => $data['hmac'],
                    "pidData"=> $data['pidData'],
                    "ts"     => $ts,
                    "dc"     => $data['dc'],
                    "mi"     => $data['mi'],
                    "dpId"   => $data['dpId'],
                    "mc"     => $data['mc'],
                    "rdsId"  => $data['rdsId'],
                    "rdsVer" => $data['rdsVer'],
                    "Skey"   => $data['sessionKey'],
                    "srno"   => $data['srno']
                // ]
            ];
    
            $response = Impsguru::guruRequest($url, 'POST', json_encode($payload), '');
        //   return $response["response"];
            // ✅ Handle response & update txn
        DB::beginTransaction();
        try {
            $result = DMTResponseHandler::handleKycRemitter($dmtTxn, $response["response"], $user, $service, $comm);
            AuditService::updateResponse($auditLog, $response);
    
            DB::commit();
            return $result;
    
        } catch (\Exception $e) {
            DB::rollBack();
            return ResponseHelper::error('KYC Process Failed', $e->getMessage(), 500);
        }
    }
	public function getBeneficiarySchema(Request $request){
        $schema = DmtValidation::getBeneficiarySchema($request);
        return ResponseHelper::success('schema fetched', $schema, 200);
    }
    public function registerBeneficiary(Request $request){
        try {
            $validator = DmtValidation::registerBeneficiary($request->all());
            if ($validator->fails()) {
                return ResponseHelper::error('Validation Failed', $validator->errors(), 422);
            }
    
            $data = $request->all();
            $bene = DmtBeneficiarie::where('sender', $request->rem_mobile)
                ->where('account_number', $request->account_number)
                ->first();

            if (!$bene) {
                DmtBeneficiarie::create([
                    'pipe' => 'DMT1',
                    'sender' => $request->rem_mobile,
                    'beneficiary_name' => $request->name,
                    'bene_id' => strtoupper(Str::random(8)),
                    'account_number' => $request->account_number,
                    'ifsc_code' => $request->ifsc,
                    'bank_name' => $request->bank_name,
                    'mobile_number' => $request->rem_mobile,
                    'is_verified' => $request->is_verified??null,
                ]);

                return response()->json([
                'status'  => 'SUCCESS',
                'message' => 'Beneficiary registered successfully'
            ], 200);
            } else {
               return response()->json([
                'status'  => 'SUCCESS',
                'message' => 'Beneficiary registered '
            ], 403);
            }
            return response()->json([
                'status'  => 'SUCCESS',
                'message' => 'Beneficiary registered successfully'
            ], 200);
    
        } catch (\Exception $e) {
            return ResponseHelper::error(
                $e->getMessage(),
                [
                    'line' => $e->getLine(),
                    'file' => $e->getFile()
                ],
                500
            );
        }
    }
    public function VerifyRegisterBeneficiary(Request $request){
        $validator = DmtValidation::VerifyRegisterBeneficiary($request->all());
        if ($validator->fails()) {
            return ResponseHelper::error('Validation Failed', $validator->errors(), 422);
        }
    
        $data = $request->all();
        $remitterMobile = $data['rem_mobile'];
        $beneficiaryId  = $data['bene_id'] ?? $data['ben_id'];
    
        $data['ip']       = $request->ip();
        $data['url']      = 'https://api.instantpay.in/fi/remit/out/domestic/v2/beneficiaryRegistrationVerify';
        $data['method']   = 'POST';
        $data['outlet_id'] = Auth::user()->instId ?? 243829;
    
        $data['payload'] = '{
            "remitterMobileNumber": "' . $remitterMobile . '",
            "beneficiaryId": "' . $beneficiaryId . '",
            "otp": "' . $request->otp . '",
            "referenceKey": "' . $request->referenceKey . '"
        }';
    
        $res = Ipay::service($data);
    
        if (!$res) {
            return ResponseHelper::error('Network Error', [], 403);
        }
        if ($res['statuscode'] === 'TXN') {
            return ResponseHelper::success('Beneficiary verified and saved successfully', $res, 200);
        }
        return ResponseHelper::error('Beneficiary verification failed', $res, 403);
    }
    public function removeBeneficiary(Request $request) {
        // Validate the request
        $validator = DmtValidation::removeBeneficiary($request->all());
        if ($validator->fails()) {
            return ResponseHelper::error('Validation Failed', $validator->errors(), 422);
        }
    
        $remitterMobile = $request->rem_mobile;
        $beneficiaryId  = $request->bene_id ?? $request->ben_id;
    
        // Find the beneficiary
        $beneficiary = DmtBeneficiarie::where('sender', $remitterMobile)
            ->where('bene_id', $beneficiaryId)
            ->first();
    
        if (!$beneficiary) {
            return ResponseHelper::error('Beneficiary not found', null, 404);
        }
    
        // Delete the beneficiary
        $beneficiary->delete();
    
        // Return success response
        return ResponseHelper::success('Deleted successfully', [
            'remitter_mobile' => $remitterMobile,
            'bene_id' => $beneficiaryId
        ], 200);
    }
    public function VerifyRemoveBeneficiary(Request $request){
        $validator = DmtValidation::VerifyRemoveBeneficiary($request->all());
        if ($validator->fails()) {
            return ResponseHelper::error('Validation Failed', $validator->errors(), 422);
        }
        $mpin=OTP::verifyMpin($request->mpin);
        if(!$mpin){
             return ResponseHelper::error("invalid mpin", [], 403);
        }
        $remitterMobile = $request->rem_mobile;
        $beneficiaryId  = $request->bene_id ?? $request->ben_id;
    
        $beneficiary = DmtBeneficiarie::where('sender', $remitterMobile)
            ->where('bene_id', $beneficiaryId)
            ->first();
    
        if (!$beneficiary) {
            return ResponseHelper::error('Beneficiary not found', [], 404);
        }
    
        $data = $request->all();
        $data['ip'] = $request->ip();
        $data['url'] = 'https://api.instantpay.in/fi/remit/out/domestic/v2/beneficiaryDeleteVerify';
        $data['method'] = 'POST';
        $data['outlet_id'] = Auth::user()->instId ?? 243829;
        $data['payload'] = '{
            "remitterMobileNumber": "' . $remitterMobile . '",
            "beneficiaryId": "' . $beneficiaryId . '",
            "otp": "' . $request->otp . '",
            "referenceKey": "' . $request->referenceKey . '"
        }';
        $res = Ipay::service($data);
        if (!$res) {
            return ResponseHelper::error('Network Error', [], 403);
        }
        if ($res['statuscode'] === 'TXN') {
            $beneficiary->delete();
            return ResponseHelper::success('Beneficiary deleted successfully', $res, 200);
        }
        return ResponseHelper::error('Beneficiary deletion failed', $res, 403);
    }
    public function dmtTxn(Request $request){
        $user = Auth::user();
    
        $validator = DmtValidation::dmtTxn($request->all());
        if ($validator->fails()) {
            return ResponseHelper::error('Validation error', $validator->errors(), 422);
        }
         $dupCheck = Uuid::check($request->client_ref,'dmtTxn1',$request);
        if($dupCheck['code'] != '200'){
            return ResponseHelper::error('Duplicate Transaction',[],402);
        }
    
    
        $mpin = OTP::verifyMpin($request->mpin);
        if (!$mpin) {
            return ResponseHelper::error("Invalid mpin", [], 403);
        }
        
        if (!in_array($user->role, ["api", "dd", "ret"])) {
            return ResponseHelper::error('Action not Authorised', '', 403);
        }
        $service = Service::find($request->operator);
        $comm = CommRule::calculateCommission($user, $service->name, $request->amount);
        if(!$comm){
                return ResponseHelper::error('Commission Slab Not Configured', '', 403);
        }
        $a_comm = AcommRule::calculateAdminCommission($service->name, $request->amount, $service->route);
        if(!$a_comm){
                return ResponseHelper::error('Admin Commission Slab Not Configured', '', 403);
        }
        if (in_array($user->role, ["api", "dd", "ret"])) {
            $comm = CommRule::finalCommission($comm, $request->amount, $service);
            $a_comm = AcommRule::finalCommission($a_comm, $request->amount);
        }
              $requiredBalance = ($request->amount + $user->lien + ($comm['ret'] ?? $comm['dd'])) * 100;
             if ($user->w1 < $requiredBalance) {
                return ResponseHelper::error('Insufficient W1 Balance', [], 403);
            }
    
        DB::beginTransaction();
        try {
            // Create DMT Transaction
            $dmtTxn = DmtService::create($user, $service, $request, $comm, $a_comm);
    
            // Debit user wallet
            WalletService::debit($user, $request->amount, $dmtTxn, $service);
    
            // Log audit
            $auditLog = AuditService::logRequest($user, $request, $service);
         DB::commit();
            } catch (\Exception $e) {
                DB::rollBack();
                return ResponseHelper::error('Transaction Init Failed', $e->getMessage(), 500);
            }
            // Prepare hash
            $data = $request->all();
            $outlet_id = $user->instId ?? 243829;
            $ben = preg_replace("/[^a-zA-Z ]/", "", $data['ben_name']);
            $hashData =
                $data['mobile_number'] .
                $data['amount'] .
                $data['ben_acc'] .
                    $ben .
                $data['ben_id'] .
                $data['ifsc'] .
                $data['type'] .
                "11" .
                $dmtTxn->txn_id .
                env('GURU_HKEY');
    
            $hash = hash('sha256', $hashData);
    
            // Prepare payload
            $payload = [
                "kyc" => "0",
                "mobileNumber" => $data['mobile_number'],
                "transferAmount" => $data['amount'],
                "beneficiaryAccount" => $data['ben_acc'],
                 "beneficiaryName" => $ben,
                "beneficiaryId" => $data['ben_id'],
                "transferMode" => $data['type'],
                "ifscCode" => $data['ifsc'],
                "operatorCode" => "11",
                "otp" => $data['otp'] ?? "",
                "referenceKey" => $data['referenceKey'] ?? "",
                "client_id" => $dmtTxn->txn_id ,
                "latitude" => $data['latitude'] ?? "",
                "longitude" => $data['longitude'] ?? "",
                "outlet_id" => $outlet_id,
                "generatedHash" => $hash
            ];
    
            // Call API
            $url = 'https://api.impsguru.com/prod/v1/dmt/domesticRemittance';
            $response = Impsguru::guruRequest($url, 'POST', json_encode($payload), 'DMTTXN');
        //  return $response;
            // Handle API response
            
        DB::beginTransaction();
        try {
            $result = DMTResponseHandler::handle($dmtTxn, $response, $user, $service, $comm);
            AuditService::updateResponse($auditLog, $response);
    
            DB::commit();
            return $result;
    
        } catch (\Exception $e) {
            DB::rollBack();
            return ResponseHelper::error('DMT Transaction Failed', $e->getMessage(), 500);
        }
    }
    public function accountVerification(Request $request){
        $user = Auth::user();
        $validator = DmtValidation::accountVerification($request->all());
        if ($validator->fails()) {
            return ResponseHelper::error('Validation error', $validator->errors(), 422);
        }
          $dupCheck = Uuid::check($request->client_ref,'accountVerification1',$request);
        if($dupCheck['code'] != '200'){
            return ResponseHelper::error('Duplicate Transaction',[],402);
        }
        $mpin = OTP::verifyMpin($request->mpin);
        if (!$mpin) {
            return ResponseHelper::error("Invalid MPIN", [], 403);
        }
        
        if (!in_array($user->role, ["api", "dd", "ret"])) {
            return ResponseHelper::error('Action not Authorised', '', 403);
        }
        $service = Service::find($request->operator);
    //   return $service->route;
        if (!$service) {
            return ResponseHelper::error('Invalid Service Selected', '', 404);
        }
        $comm = CommRule::calculateCommission($user, $service->name, 1);
        if (!$comm) {
            return ResponseHelper::error('Commission Slab Not Configured', '', 403);
        }
        $a_comm = AcommRule::calculateAdminCommission($service->name, 1, $service->route);
        if (!$a_comm) {
            return ResponseHelper::error('Admin Commission Slab Not Configured', '', 403);
        }
    
        if (in_array($user->role, ["api", "dd", "ret"])) {
            $comm = CommRule::finalCommission($comm, 1, $service);
            $a_comm = AcommRule::finalCommission($a_comm, 1);
        }
        $baseCommission = $comm['ret'] ?? $comm['dd'] ?? 0;
        $totalAmount = 1 + $baseCommission + $baseCommission * 0.18;
        if ($user->w1 < (1 + $totalAmount)* 100) {
            return ResponseHelper::error('Insufficient W1 Balance', '', 403);
        }
        DB::beginTransaction();
        try {
            $dmtTxn = DmtService::createVerification($user, $service, $request, $comm,$a_comm);
            WalletService::debit($user, 1, $dmtTxn, $service);
            $auditLog = AuditService::logRequest($user, $request, $service);
            DB::commit();
        } catch (\Exception $e) {
            DB::rollBack();
            return ResponseHelper::error('Verification Init Failed', $e->getMessage(), 500);
        }
        $data = $request->all();
        $data['operator'] = 'Verification';
        $data['ben_name'] = str_replace("&", "and", $data['ben_name']);
        $data['ip'] = $request->ip();
        $data['url'] = 'https://api.impsguru.com/prod/v1/verification/bankAccountVerification';

        $latitude = $request->latitude ?? '0';
        $longitude = $request->longitude ?? '0';

        $hashData = $dmtTxn->sender_mobile .
            $dmtTxn->account_number .
            $dmtTxn->ifsc_code .
            "12" .
            $dmtTxn->client_ref .
            env('GURU_HKEY');

        $hash = hash("sha256", $hashData);

        $payload = json_encode([
            "mobileNumber"        => $dmtTxn->sender_mobile,
            "beneficiaryAccount"  => $dmtTxn->account_number,
            "beneficiaryName"     => $dmtTxn->ben_name,
            "ifscCode"            => $dmtTxn->ifsc_code,
            "operatorCode"        => "12",
            "client_id"           => $dmtTxn->client_ref,
            "latitude"            => $latitude,
            "longitude"           => $longitude,
            "generatedHash"       => $hash,
            "env"                 => "Live"
        ]);

        $data['method'] = 'POST';
        $data['outlet_id'] = $user->instId ?? 243829;
     
        $response = $this->router($data['url'], $data['method'], $payload, $service);
        // return $response;
        DB::beginTransaction();
        try {
            $result = DMTResponseHandler::handleVerification($dmtTxn, $response, $user, $service, $comm);
            AuditService::updateResponse($auditLog, $response);
            DB::commit();
            return $result;
        } catch (\Exception $e) {
            DB::rollBack();
            return ResponseHelper::error('Verification Update Failed', $e->getMessage(), 500);
        }
    }
    public function router($url, $method, $payload, $service) {
        if ($service->route == 'IMGURU'||$service->route == 'IMPSGURU') {
            return Impsguru::guruRequest($url, $method, $payload, 'ACCVERIFY');
        } elseif ($service->route == 'ENKASH') {
            return Enkash::service(json_decode($payload, true));
        }
        return ResponseHelper::error('Invalid Route', '', 404);
    }
    public function transactionOtpDmt1(Request $request){
        $user=Auth::user();
        $validator = DmtValidation::transactionOtpDmt1($request->all());
        if ($validator->fails()) {
            return ResponseHelper::error('Validation Failed', $validator->errors(), 422);
        }
    
        $data = $request->all();
        $outlet_id = Auth::user()->instId ?? 243829;
        $url = 'https://api.impsguru.com/prod/v1/dmt/getOtpDmt1';
        $payload = '{"outlet_id":"'.$outlet_id.'",
                    "mobileNumber": "'.$data['number'].'",
        			"amount" : "'.$data['amount'].'",
        			"referenceKey" : "'.$data['referenceKey'].'",
        			"ip" : "'.$request->ip().'"
        }';
        $method = 'POST';
        $response = Impsguru::guruRequest($url,$method,$payload,'DMTTXNOTP');
        // if($user->id==5){
        $time = Date::now()->format('ymdHis'); 
        $random = mt_rand(10000, 99999); 
        $uuid = $time . $random;
        $data = Uuid::create([
            'uuid' => $uuid,
            'status' => 1,
            'user_id' =>$user->id,
        ]);
            $response['data']['uuid']=$data['uuid'];
        // }
        return $response;
        if(isset($response)){
            if($response['statuscode'] == 'TXN'){
                return ResponseHelper::success($response['status'], $response['data'], 200);
            }elseif($response['statuscode'] == 'OTP'){
                return ResponseHelper::success($response['status'], $response['data'], 200);
            }elseif($response['statuscode'] == 'ERR'){
                return ResponseHelper::error($response['status'], $response['data'], 403);
            }else{
                return ResponseHelper::error($response['status'], $response['data'], 403);
            }
        }
        return ResponseHelper::error('No Response From Bank', '', 403);
    }
   

}
