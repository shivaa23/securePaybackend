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
use App\Modules\Service\Models\Service;
use Auth;
use App\Helper\ResponseHelper;
use App\Modules\Integration\Models\Ipay;
use App\Modules\Dmt\Services\DmtService; 
use Illuminate\Support\Str;
use Illuminate\Support\Facades\DB;
use App\Modules\WalletLedger\Services\WalletService;
use App\Modules\AuditLog\Services\AuditService;
use App\Modules\Dmt\Services\DMTResponseHandler;
use App\Modules\Auth\Models\Otp;
define('ENCKEY','b55961e2b5bf1441b55961e2b5bf1441');

class IpayController extends BaseController
{
    use ApiResponser;

    public function __construct()
    {
        $this->middleware('auth', ['except' => ['']]);
        $this->middleware('check.active', ['except' => '']);

    }
    
    public function getBanks(Request $request){
		$data['ip'] = $request->ip();
        $data['url'] = 'https://api.instantpay.in/fi/remit/out/domestic/v2/banks';
        $data['payload'] = '';
        $data['method'] = 'POST';
		//$data['outlet_id'] = (isset(Auth::user()->instId))?Auth::user()->instId : 243829;
        $response = Ipay::service($data);
		if(isset($response)){
            if($response['statuscode'] == 'TXN'){
                return ResponseHelper::success('Banks Fetched Successfully', $response['data'], 200);
            }else{
                return ResponseHelper::error('Service Provider Down', '', 403);
            }
        }
	}

	public function remitterStatus(Request $request)
    {
    	$validator = DmtValidation::remitterStatus($request->all());
        if ($validator->fails()) {
            return ResponseHelper::error('Validation Failed', $validator->errors(), 422);
        }
    
        $data['ip'] = $request->ip();
        $data['url'] = 'https://api.instantpay.in/fi/remit/out/domestic/v2/remitterProfile';
        $data['payload'] = json_encode(['mobileNumber' => $request->mobile_number]);
        $data['method'] = 'POST';
    
        $response = Ipay::service($data);
    
        //Log::info('Remitter API response: ' . json_encode($response));
    
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
                //  return ResponseHelper::success($response['status'], $response['data'], 403);
            case 'OTP':
                return ResponseHelper::success($response['status'], $response['data'], 200);
            case 'ERR':
            default:
                return ResponseHelper::error($response['status'] ?? 'Unknown Error', $response['data'] ?? [], 403);
        }
    }
	public function registerRemitter(Request $request){
		$validator = DmtValidation::registerRemitter($request->all());
        if ($validator->fails()) {
            return ResponseHelper::error('Validation Failed', $validator->errors(), 422);
        }
		$data = $request->all();
		$aadhaarNumber = $data['aadhaar_number'];
        $encryptionKey = ENCKEY;
        $ivlen = openssl_cipher_iv_length('aes-256-cbc');
        $iv = openssl_random_pseudo_bytes($ivlen);
        $ciphertext = openssl_encrypt($aadhaarNumber,'aes-256-cbc', $encryptionKey, OPENSSL_RAW_DATA, $iv);
        $encryptedData = base64_encode($iv . $ciphertext);
		$data['ip'] = $request->ip();
        $data['url'] = 'https://api.instantpay.in/fi/remit/out/domestic/v2/remitterRegistration';
        $data['payload'] = '{
			"mobileNumber": "'.$data['mobile_number'].'",
			"encryptedAadhaar" : "'.$encryptedData.'",
			"referenceKey" : "'.$data['reference_key'].'"
		}';
        $data['method'] = 'POST';
        //$data['outlet_id'] = Auth::user()->instId;
        $response = Ipay::service($data);
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
	public function validateRemitter(Request $request){
		$validator = DmtValidation::validateRemitter($request->all());
        if ($validator->fails()) {
            return ResponseHelper::error('Validation Failed', $validator->errors(), 422);
        }
		$data = $request->all();
		$data['ip'] = $request->ip();
        $data['url'] = 'https://api.instantpay.in/fi/remit/out/domestic/v2/remitterRegistrationVerify';
        $data['payload'] = '{
			"mobileNumber":"'.$data['rem_number'].'",
			"otp" : "'.$data['otp'].'",
			"referenceKey": "'.$data['reference_key'].'"
		}';
        $data['method'] = 'POST';
        //$data['outlet_id'] = Auth::user()->instId;
        $response = Ipay::service($data);
        if(isset($response)){
            if($response['statuscode'] == 'TXN'){
                return ResponseHelper::success($response['status'], $response['data'], 200);
            }elseif($response['statuscode'] == 'KYC'){
                return ResponseHelper::success($response['status'], $response['data'], 200);
            }elseif($response['statuscode'] == 'ERR'){
                return ResponseHelper::error($response['status'], $response['data'], 403);
            }else{
                return ResponseHelper::error($response['status'], $response['data'], 403);
            }
        }
        return ResponseHelper::error('No Response From Bank', '', 403);
	}
	public function kycRemitter(Request $request){
		 $validator = DmtValidation::kycRemitter($request->all());
        if ($validator->fails()) {
            return ResponseHelper::error('Validation Failed', $validator->errors(), 422);
        }
		$data = $request->all();
		$user = Auth::user();
        $service = Service::find($request->operator);
        $comm = CommRule::calculateCommission($user, $service->name, 0);
        
		if (in_array($user->role, ["api", "dd", "ret"])) {
            $comm = CommRule::finalCommission($comm, 0,$service);
        }
        return $comm;
    
        if ($user->w1 <  ($comm['ret']?? $comm['dd'])) {
            return ResponseHelper::error('Insufficient W1 Balance', '', 403);
        }
		$data['operator'] = 'RKY';
        $data['amount'] = 0;
        $data['type'] = 'REMITTER';
        $data['ip'] = $request->ip();
		$response = Transaction::newTransaction($data);
		if(isset($response)){
			if($response['code'] == 200) {
				$captureType = $request->captureType ?? "FINGER";
				$txn = $response['data'];
				$ts = $data['ts'] ?? "";
				$data['ip'] = $request->ip();
				$data['url'] = 'https://api.instantpay.in/fi/remit/out/domestic/v2/remitterKyc';
				$data['payload'] = '{
				"mobileNumber":"'.$data['number'].'",
				"referenceKey": "'.$data['referenceKey'].'",
				"latitude": "'.$data['latitude'].'",
				"longitude": "'.$data['longitude'].'",
				"captureType" : "'.$captureType.'",
				"externalRef":"'.$txn->order_id.'",
				"consentTaken" : "Y",
				"biometricData": {  
					"ci" : "'.$data['ci'].'",
					"hmac" : "'.$data['hmac'].'",
					"pidData":"'.$data['pidData'].'",
					"ts":"'.$ts.'",
					"dc" : "'.$data['dc'].'",
					"mi" : "'.$data['mi'].'",
					"dpId" : "'.$data['dpId'].'",
					"mc" : "'.$data['mc'].'",
					"rdsId" : "'.$data['rdsId'].'",
					"rdsVer" :"'.$data['rdsVer'].'",
					"Skey" : "'.$data['sessionKey'].'",
					"srno":"'.$data['srno'].'"
					}
				}';
				$data['method'] = 'POST';
				$data['outlet_id'] = Auth::user()->instId;
				$res = InstantPay::dmr($data);
				$log = ['kycRem'=>$data, 'fileName' =>'RemitterDMT1','response'=>$res];
				Logs::logs($log);
				if(isset($res)){
					if($res['statuscode'] == 'TXN'){
						$txn->update(['status'=>'SUCCESS','api_response'=>$res]);
						return response()->json([
							'status' => 'SUCCESS',
							'message' => 'KYC Successfully Done'
						],200);
					}elseif($res['statuscode'] == 'ERR' || $res['statuscode'] == 'SPD' || $res['statuscode'] == 'SPE' || $res['statuscode'] == 'IST'){
						$ret_closing = $user->w1/100;
						$user->increment('w1', $txn->net_amount * 100);
						if($user->role == 'Ret'){
							$ad = User::find($user->parent)->first();
							if(isset($ad)){
								$ad->decrement('w1', ($txn->ad_comm-$txn->ad_tds) * 100);
								$md = User::find($ad->parent);
								if(isset($md) && $md->role == 'Md'){
									$md->decrement('w1', ($txn->md_comm - $txn->md_tds) * 100);
								}
							}
						}
						$txn->update(['status'=>'FAILED',
										'op_id'=>$res['status'],
										'w1'=>$ret_closing,
										'ad_closing'=>isset($ad) ? $ad->w1/100 : 0,
										'md_closing'=>isset($md) ? $md->w1/100 : 0,
										'api_response'=>$res]);
						return response()->json([
							'status' => 'FAILED',
							'message' => $res['status']
						],403);
					}else{
						return response()->json([
							'status' => 'FAILED',
							'message' => $res['status']
						],403);
					}
				}else{
					return response()->json([
						'status' => 'FAILED',
						'message' => 'Service Provider Down'
					],403);
				}
			}else{
				return response()->json([
					'status' => 'FAILED',
					'message' => $response['message']
				],403);
			}
		}else{
			return response()->json([
				'status' => 'FAILED',
				'message' => 'Something Went Wrong'
			],403);
		}
		
	}
    public function registerBeneficiary(Request $request)
    {
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
                    'is_verified' => null,
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
    
            // $beneficiary = DmtBeneficiarie::where('sender', $data['rem_mobile'])
            //     ->where('pipe', 'DMT1')
            //     ->where('account_number', $data['account_number'])
            //     ->first();
    
            // if (!$beneficiary) {
              
            //     DmtBeneficiarie::create([
            //         'sender'            => $data['rem_mobile'],
            //         'bene_id'           => 'BENE_' . strtoupper(Str::random(8)), // generated here
            //         'beneficiary_name'  => $data['name'],
            //         'account_number'    => $data['account_number'],
            //         'ifsc_code'         => $data['ifsc'],
            //         'bank_name'         => $data['bank_name'] ?? $data['ifsc'],
            //         'mobile_number'     => $data['ben_mobile'] ?? $data['rem_mobile'],
            //         'is_verified'       => 0,
            //         'is_active'         => 1,
            //         'pipe'              => 'DMT1'
            //     ]);
            // } else {
            //     if ($beneficiary->is_active == 0) {
            //         $beneficiary->update(['is_active' => 1]);
            //     }
            // }
    
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
    public function VerifyRegisterBeneficiary(Request $request)
    {
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
    public function removeBeneficiary(Request $request)
    {
        $validator = DmtValidation::removeBeneficiary($request->all());
        if ($validator->fails()) {
            return ResponseHelper::error('Validation Failed', $validator->errors(), 422);
        }
    
        $remitterMobile = $request->rem_mobile;
        $beneficiaryId  = $request->bene_id ?? $request->ben_id;
    
        $data = $request->all();
        $data['ip'] = $request->ip();
        $data['url'] = 'https://api.instantpay.in/fi/remit/out/domestic/v2/beneficiaryDelete';
        $data['method'] = 'POST';
        $data['outlet_id'] = Auth::user()->instId ?? 243829;
    
        // Raw JSON string payload
        $data['payload'] = '{
            "remitterMobileNumber": "' . $remitterMobile . '",
            "beneficiaryId": "' . $beneficiaryId . '"
        }';
    
        $res = Ipay::service($data);
    
        if (!$res) {
            return ResponseHelper::error('Network Error', [], 403);
        }
    
        if ($res['statuscode'] === 'OTP') {
            return ResponseHelper::success('OTP sent successfully', [
                'otp_ref_id'    => $res['data']['referenceKey'] ?? null,
                'beneficiaryId' => $res['data']['beneficiaryId'] ?? null
            ], 200);
        }
    
        if ($res['statuscode'] === 'ERR') {
            return ResponseHelper::error('Beneficiary deletion failed', $res, 403);
        }
    
        return ResponseHelper::error('Unexpected response', $res['status'] ?? 'Unknown', 403);
    }
    public function getBeneficiarySchema(Request $request){
          $schema = DmtValidation::getBeneficiarySchema($request);
           return ResponseHelper::success('schema fetched', $schema, 200);
     }
         public function getRegisterRemitterSchema(Request $request){
          $schema = DmtValidation::getRegisterRemitterSchema($request);
           return ResponseHelper::success('schema fetched', $schema, 200);
     }
    public function VerifyRemoveBeneficiary(Request $request)
    {
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
        public function dmtTxn(Request $request)
        {
           $user = Auth::user();
            $validator = DmtValidation::dmtTxn($request->all());
            if ($validator->fails()) {
                return ResponseHelper::error('Validation error', $validator->errors(),422);
            }
              $mpin=OTP::verifyMpin($request->mpin);
            if(!$mpin){
                 return ResponseHelper::error("invalid mpin", [], 403);
            }
             if (!in_array($user->role, ["api", "dd", "ret"])) {
                return ResponseHelper::error('Action not Authorised', '', 403);
            }
        
            if ($user->w1 < $request->amount * 100) {
                return ResponseHelper::error('Insufficient W1 Balance', '', 403);
            }
        
            $service = Service::find($request->operator);
          
            $comm = CommRule::calculateCommission($user, $service->name, $request->amount);
            if (in_array($user->role, ["api", "dd", "ret"])) {
                $comm = CommRule::finalCommission($comm, $request->amount,$service);
            }
       
            DB::beginTransaction();
            try {  
               
                $dmtTxn = DmtService::create($user, $service, $request, $comm);
             
                WalletService::debit($user, $request->amount, $dmtTxn, $service);
                
                $auditLog = AuditService::logRequest($user, $request, $service);
        
                DB::commit();
            } catch (\Exception $e) {
                DB::rollBack();
                return ResponseHelper::error('Recharge Init Failed', $e->getMessage(), 500);
            }
            
            $data = $request->all();
                $data['DUMMY']="DUMMY";
            $data['ben_name']   = str_replace("&", "and", $data['ben_name']);
            $data['ip']         = $request->ip();
            $data['url']        = 'https://api.instantpay.in/fi/remit/out/domestic/v2/transaction';
            $data['payload'] = '{
                "remitterMobileNumber": "' . $data['mobile_number'] . '",
                "accountNumber": "' . $data['ben_acc'] . '",
                "ifsc": "' . $data['ifsc'] . '",
                "transferMode": "' . $data['type'] . '",
                "transferAmount": "' . $data['amount'] . '",
                "latitude": "' . $data['latitude'] . '",
                "longitude": "' . $data['longitude'] . '",
                "referenceKey": "' . $data['referenceKey'] . '",
                "otp": "' . $data['otp'] . '",
                "externalRef": "' . $dmtTxn->txn_id . '"
            }';

            $data['method']     = 'POST';
            // return $data['payload'];
           // $data['outlet_id']  = $user->instId;
            $response = Ipay::service($data);
            // return $response;
         
          
            DB::beginTransaction();
            try {
                $result = DMTResponseHandler::handle($dmtTxn, $response, $user, $service, $comm);
                AuditService::updateResponse($auditLog, $response);
                DB::commit();
                return $result;
            } catch (\Exception $e) {
                DB::rollBack();
                return ResponseHelper::error('Recharge Update Failed', $e->getMessage(), 500);
            }
            
        }
      public function accountVerification(Request $request)
      {
        $user = Auth::user();
        $validator = DmtValidation::accountVerification($request->all());
        if ($validator->fails()) {
            return ResponseHelper::error('Validation error', $validator->errors(), 422);
        }
       $mpin=OTP::verifyMpin($request->mpin);
        if(!$mpin){
             return ResponseHelper::error("invalid mpin", [], 403);
        }
        if (!in_array($user->role, ["api", "dd", "ret"])) {
            return ResponseHelper::error('Action not Authorised', '', 403);
        }
    

        $amount = 1;
           
        $service = Service::find($request->operator);
        $comm = CommRule::calculateCommission($user, $service->name, $amount);
        if (in_array($user->role, ["api", "dd", "ret"])) {
            $comm = CommRule::finalCommission($comm, $amount,$service);
        }
       $baseCommission = $comm['ret'] ?? $comm['dd'] ?? 0;
       $totalAmount = $amount + $baseCommission + round($baseCommission * 0.18, 2);
        // return $comm;
        if ($user->w1 < $amount*100 + $totalAmount) {
            return ResponseHelper::error('Insufficient W1 Balance', '', 403);
        }
        DB::beginTransaction();
        try {
            $dmtTxn = DmtService::createVerification($user, $service, $request, $comm,$totalAmount);
         
            WalletService::debit($user, $totalAmount, $dmtTxn, $service);
            $auditLog = AuditService::logRequest($user, $request, $service);
            DB::commit();
        } catch (\Exception $e) {
            DB::rollBack();
            return ResponseHelper::error('Verification Init Failed', $e->getMessage(), 500);
        }
        $data = $request->all();
        $data['operator']   = 'Verification';
        $data['ben_name']   = str_replace("&", "and", $data['ben_name']);
        $data['ip']         = $request->ip();
        $data['url']        = 'https://api.instantpay.in/identity/verifyBankAccount';
        $data['payload']    = '{
            "payee": {
                "accountNumber": "'.$dmtTxn->account_number.'",
                "bankIfsc": "'.$dmtTxn->ifsc_code.'"
            },
            "externalRef": "'.$dmtTxn->txn_id.'",
            "latitude": "'.$data['latitude'].'",
            "longitude": "'.$data['longitude'].'",
            "consent": "Y",
            "isCached": "0"
        }';
        $data['method']     = 'POST';
        $data['outlet_id']  = $user->instId ?? 243829;
        $response = Ipay::service($data);
        DB::beginTransaction();
        try {
            $route = $request->route?? 'DMT1';
            $result = DMTResponseHandler::handleVerification($dmtTxn, $response, $user,$service, $comm, $route);
            AuditService::updateResponse($auditLog, $response);
            DB::commit();
            return $result;
        } catch (\Exception $e) {
            DB::rollBack();
            return ResponseHelper::error('Verification Update Failed', $e->getMessage(), 500);
        }
    }

        public function transactionOtpDmt1(Request $request)
        {
            $validator = DmtValidation::transactionOtpDmt1($request->all());
            if ($validator->fails()) {
                return ResponseHelper::error('Validation Failed', $validator->errors(), 422);
            }
        
            $data = $request->all();
            $data['ip']        = $request->ip();
            $data['url']       = 'https://api.instantpay.in/fi/remit/out/domestic/v2/generateTransactionOtp';
            $data['payload']   = json_encode([
                "remitterMobileNumber" => $data['number'],
                "amount"               => $data['amount'],
                "referenceKey"         => $data['referenceKey'],
            ]);
            $data['method']    = 'POST';
            $data['outlet_id'] = Auth::user()->instId;
        
            $res = Ipay::service($data);
        
            if ($res) {
                if ($res['statuscode'] === 'OTP') {
                    return ResponseHelper::success(
                        $res['status'] ?? 'OTP generated successfully',
                        $res['data'] ?? [],
                        200
                    );
                }
        
                return ResponseHelper::error(
                    $res['status'] ?? 'Transaction OTP generation failed',
                    $res,
                    403
                );
            }
        
            return ResponseHelper::error(
                'Service Provider Down',
                [],
                503
            );
        }


}
