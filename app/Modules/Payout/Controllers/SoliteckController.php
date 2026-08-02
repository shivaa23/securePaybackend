<?php

namespace App\Modules\Payout\Controllers;

use Laravel\Lumen\Routing\Controller as BaseController;
use App\Traits\ApiResponser;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use App\Modules\Payout\Models\PayoutTxn;
use App\Modules\Payout\Models\PayoutValidation;
use App\Modules\Payout\Models\PayoutSender;
use App\Modules\Payout\Models\Purpose;
use App\Modules\Commission\Models\CommRule;
use App\Modules\Commission\Models\AcommRule;
use App\Modules\Service\Models\Service;
use App\Modules\Integration\Models\Impsguru;
use App\Modules\Integration\Models\Soliteck;
use Auth;
use DB;
use App\Helper\ResponseHelper;
use App\Modules\Auth\Models\Otp;
use App\Modules\User\Models\User;
use App\Modules\Payout\Services\PayoutService;
use App\Modules\WalletLedger\Services\WalletService;
use App\Modules\AuditLog\Services\AuditService;
use App\Modules\Payout\Services\PayoutResponseHandler;
use App\Modules\Message\Models\Sms;
use App\Modules\Template\Models\Template;
use App\Modules\Payout\Models\PayoutBeneficiary;
use App\Modules\Payout\Models\SoliteckValidation;
use Carbon\Carbon;
use Validator;
use App\Modules\Payout\Models\SenderValidation;
use App\Modules\BusinessDetail\Models\BusinessAddress;
use App\Modules\BusinessDetail\Models\BusinessIdentification;
use App\Modules\Misc\Models\Uuid;
class SoliteckController extends BaseController
{
    use ApiResponser;

    public function __construct()
    {
        $this->middleware('auth', ['except' => ['']]);
        $this->middleware('check.active', ['except' => '']);
        $this->middleware('check.whitelist', ['except' => 'getPayoutTxns','getPayoutSchema','upiVerifiaction','createPurpose','getPurpose']);
    }
    
    public function getPayoutSchema(){
        $schema=PayoutValidation::getPayoutSchema();
        return ResponseHelper::success('Schema fetched', $schema, 200);
    }


    public function getSender2(Request $request)
    {
        $user = Auth::user();
        if (!in_array($user->role, ['ret', 'dd', 'api'])) {
            return ResponseHelper::error('Unauthorized action', [], 403);
        }
        if (!$request->filled('mobile_number')) {
            return ResponseHelper::error('Mobile number is required', [], 400);
        }
        $sender = PayoutSender::with(['beneficiary' => function ($query) {
            $query->where('type', 'SOLITECK');
        }])
        ->where('mobile_number', $request->mobile_number)
        ->where('pipe', 'SOLITECK')
        ->first();
    
        if (!$sender) {
            return ResponseHelper::error('Sender not found', [], 404);
        }
    
        return ResponseHelper::success('Success', $sender, 200);
    }
 public function getSender3(Request $request){
        $user = Auth::user();
        if (!in_array($user->role, ["ret", "dd", "api"])) {
            return ResponseHelper::error('Unauthorised Action', '', 403);
        }
    
        if (!$request->mobile_number) {
            return ResponseHelper::error('Mobile number required', '', 403);
        }
    
        $sender = PayoutSender::with(['beneficiary' => function ($query) {
            $query->where('type', 'SOLITECK');
        }])->where('mobile_number', $request->mobile_number)
          ->where('pipe', 'SOLITECK')
          ->first();
    
        if (!$sender) {
            return ResponseHelper::success('Sender Not Found', '', 200);
        }
    
        if ($sender->is_active == 0) {
            $otp = Otp::loginOtp($user->id);
            $template = Template::where('name', 'MOB_VERIFY')->first();
    
            if (!$template) {
                return ResponseHelper::error('Template not found', [], 403);
            }
    
            $message = str_replace('{$otp}', $otp['otp'], $template->message);
            Sms::send([
                "number" => $sender->mobile_number,
                "message" => $message,
                "tempid" => $template->temp_id
            ], $user);
    
            return ResponseHelper::error('The number is inactive', [
                'mobile_number' => $sender->mobile_number,
                'otp_ref' => $otp['otp_ref']
            ], 403);
        }
    
        if ($sender->kyc_status == 'pending') {
            return ResponseHelper::success('Sender Not Verified', '', 200);
        }
    
        return ResponseHelper::success('Payout transactions fetched', $sender, 200);
    }
     public function verifyRemitterLogin(Request $request){
        $validator = SenderValidation::verifySender($request->all());
        if ($validator->fails()) {
            return ResponseHelper::error('Validation failed', $validator->errors(), 422);
        }
    
        $user = Auth::user();
        if (!in_array($user->role, ['ret', 'dd', 'api'])) {
            return ResponseHelper::error('Unauthorized Action', null, 403);
        }
    
        $sender = PayoutSender::where('mobile_number', $request->mobile_number)-> where('pipe', "SOLITECK")->first();
        if (!$sender) {
            return ResponseHelper::error('Sender not found', null, 404);
        }
    
        $otpVerification = Otp::verifyOtp($request);
        if (!$otpVerification['success']) {
            return ResponseHelper::error(
                $otpVerification['message'],
                $otpVerification['errors'] ?? null,
                403
            );
        }
         $full_name = trim($sender->sender_name);
        $name_parts = explode(' ', $full_name);
        
        $f_name = $name_parts[0]; 
        $l_name = isset($name_parts[1]) ? $name_parts[1] : ''; 

        $payload = [
            'ClientReq'      => uniqid('req_'),
            'RemitterMobile' => (string) $sender->mobile_number,
            'RemiFName'      =>$f_name,
            'RemiLName'      => $l_name,
            'Token'          => Soliteck::generateToken(),
        ];
    
        $response = Soliteck::service([
            'url'     => 'https://api-uat.soliteck.in/api/Payout/PayoutRemitterLogIn-withoutOtp',
            'payload' => json_encode($payload),
            'method'  => 'POST',
        ]);
    
        if (!is_array($response) || !isset($response['responseCode'])) {
            return ResponseHelper::error('Something went wrong', $response, 400);
        }
    
        switch ($response['responseCode']) {
            case 200:
            case 201:
                $remitterData = json_decode($response['responseData'], true);
                $sender->kyc_status = 'verified';
                $sender->is_active  = 1;
                // $sender->rem_limit  = $remitterData['AvailableLimit'] ?? 0;
                $sender->save();
    
                return ResponseHelper::success(
                    $response['responseMessage'] ?? 'Remitter verified successfully',
                    $remitterData,
                    $response['responseCode']
                );
    
            case 401:
                return ResponseHelper::error(
                    $response['responseMessage'] ?? 'Token expired',
                    null,
                    403
                );
    
            default:
                return ResponseHelper::error(
                    $response['responseMessage'] ?? 'Something went wrong',
                    null,
                    400
                );
        }
    }
    public function remitterLogin(Request $request)
    {
        $user=Auth::user();
        $validator = Validator::make($request->all(), [
            'mobile_number' => 'required|digits:10',
            'f_name'        => 'required|string|max:50',
            'l_name'        => 'required|string|max:50',
        ]);
    
        if ($validator->fails()) {
            return ResponseHelper::error($validator->errors(), [], 400);
        }
    
        $sender = PayoutSender::with(['beneficiary' => function ($query) {
            $query->where('type', 'SOLITECK');
        }])
        ->where('mobile_number', $request->mobile_number)
        ->where('pipe', 'SOLITECK')
        ->first();
    
        // if ($sender) {
        //     return ResponseHelper::success('Sender already exists', $sender, 200);
        // }
        $f_name = preg_replace('/[^a-zA-Z\s]/', '', preg_replace('/\s+/', ' ', trim($request->f_name)));
        $l_name = preg_replace('/[^a-zA-Z\s]/', '', preg_replace('/\s+/', ' ', trim($request->l_name)));
        

        $payload = [
            "ClientReq"      => uniqid('req_'),
            "RemitterMobile" => $request->mobile_number,
            "RemiFName"          => $f_name,
            "RemiLName"          =>  $l_name,
            "Token"          => Soliteck::generateToken()
        ];
        // return $payload;
    
        $data = [
            'url'     => 'https://api-uat.soliteck.in/api/Payout/PayoutRemitterLogIn-withoutOtp',
            'payload' => json_encode($payload),
            'method'  => 'POST'
        ];
    
        $response = Soliteck::service($data);
        //  return $response;
        if (!is_array($response) || !isset($response['responseCode'])) {
            return ResponseHelper::error('Something went wrong', [], 400);
        }
    
        switch ($response['responseCode']) {
            case 200:
            case 201:
            $remitterData = json_decode($response['responseData'], true); // array
            $payouSenderNew = new PayoutSender();
            $payouSenderNew->sender_name   = $remitterData['RemitterName'] ?? null;
            $payouSenderNew->mobile_number = $remitterData['RemitterMobile'] ?? null;
            $payouSenderNew->kyc_status    = "verified";
            $payouSenderNew->is_active    = 1;
            $payouSenderNew->pipe           = "SOLITECK";
            // $payouSenderNew->rem_limit      = $remitterData['AvailableLimit'] ?? 0;
            $payouSenderNew->user_id        = $user->id;
            $payouSenderNew->save();

                return ResponseHelper::success($response['responseMessage'], $remitterData, $response['responseCode']);
    
            case 401:
                return ResponseHelper::error($response['responseMessage'] ?? 'Token expired', [], 403);
    
            default:
                return ResponseHelper::error($response['responseMessage'] ?? 'Something went wrong', [], 400);
        }
    }
      public function remitterLogin2(Request $request){
     $validator = Validator::make($request->all(), [
            'mobile_number' => 'required|digits:10',
            'f_name'        => 'required|string|max:50',
            'l_name'        => 'required|string|max:50',
        ]);
    
        if ($validator->fails()) {
            return ResponseHelper::error($validator->errors(), [], 400);
        }
    
        $user = Auth::user();
        if (!in_array($user->role, ["ret", "dd", "api"])) {
            return ResponseHelper::error('Unauthorised Action', '', 403);
        }
        $f_name = preg_replace('/[^a-zA-Z\s]/', '', preg_replace('/\s+/', ' ', trim($request->f_name)));
        $l_name = preg_replace('/[^a-zA-Z\s]/', '', preg_replace('/\s+/', ' ', trim($request->l_name)));
    
        $sender = new PayoutSender();
        $sender->user_id = $user->id;
       $sender->sender_name = trim($f_name . ' ' . $l_name);
        $sender->email = $request->email??$user->email;
        $sender->pipe  = "SOLITECK";
        $sender->user_id = $user->id;
        $sender->mobile_number = $request->mobile_number;
    
        if ($sender->save()) {
            $otp = Otp::loginOtp($user->id);
            $template = Template::where('name', 'MOB_VERIFY')->first();
    
            if (!$template) {
                return $this->jsonResponse('1001', 'Template not found', null, null, 500);
            }
    
            $message = str_replace('{$otp}', $otp['otp'], $template->message);
            Sms::send([
                "number" => $sender->mobile_number,
                "message" => $message,
                "tempid" => $template->temp_id
            ], $user);
    
            return ResponseHelper::success('Sender Registered Successfully', [
                'sender'  => $sender,
                'otp_ref' => $otp['otp_ref']
            ], 200);
        }
    
        return ResponseHelper::error('Sender Registration Failed', '', 403);
    }
    

    /**
     * Remitter Registration
     */
    public function remitterRegistration(Request $request)
    {
          $validator = SoliteckValidation::registerRemitter($request->all());

        if ($validator->fails()) {
            return ResponseHelper::error($validator->errors(), [], 400);
        }

    $payload = [
    "RemitterMobile" => $request->mobile_number,
    "Token"          => Soliteck::generateToken(),
    "DBStatus"       => (string) $request->db_status,
    "VendorId"       => (string)$request->vendor_id,
    "OTP"            => $request->otp,
    "ClientReq"      => (string) uniqid('req_'), 
    ];
    // return $payload;

    if ($request->db_status == 0) {
        $payload['FName']   = $request->fname;
        $payload['LName']   = $request->lname;
        $payload['Pincode'] = $request->pincode;
    }
        $data = [
            'url'     => 'https://api-uat.soliteck.in/uat/api/PayoutTxn/RemitterRegistration',
            'payload' => json_encode($payload),
            'method'  => 'POST'
        ];

        $response = Soliteck::service($data);
        return $response;
        if (!is_array($response) || !isset($response['responseCode'])) {
            return ResponseHelper::error('Something went wrong', [], 400);
        }

        switch ($response['responseCode']) {
            case 200:
                return ResponseHelper::success($response['responseMessage'], $response['responseData'], 200);

            case 401:
                return ResponseHelper::error($response['responseMessage'] ?? 'Token expired', [], 401);

            default:
                return ResponseHelper::error($response['responseMessage'] ?? 'Something went wrong', [], 400);
        }
    }

    public function getBanks(Request $request)
    {
        $req="nileshnathalalbujad.11.2083@SoliteckPay";
        $data = [
            'url'     => 'https://api-uat.soliteck.in/api/Payout/GetDMTBanksForAPIUser',
            'payload' => json_encode($req),
            'method'  => 'POST'
        ];
    
        $response = Soliteck::service($data);
     
        if (is_array($response) && isset($response['responseCode'])) {
            if ($response['responseCode'] === 200) {
                $banks = json_decode($response['responseData'], true); 
                return ResponseHelper::success($response['responseMessage'], $banks, 200);
            } elseif ($response['responseCode'] === 401) {
                return ResponseHelper::error($response['responseMessage'] ?? 'Token expired', [], 401);
            } else {
                return ResponseHelper::error($response['responseMessage'] ?? 'Something went wrong', [], 400);
            }
        }
    
        return ResponseHelper::error('Something went wrong', [], 400);
    }
    public function getIfscByBankCode($bankCode)
    {
        if (!$bankCode) {
            return ['error' => true, 'message' => 'Bank code is required'];
        }
    
        $payload = [
            'BankCode' => $bankCode
        ];
    
        $data = [
            'url'     => 'https://api-uat.soliteck.in/api/Payout/ApiUserGetMasterIFSCForBank',
            'payload' => json_encode($bankCode),
            'method'  => 'POST'
        ];
    
        $response = Soliteck::service($data);
    //  return $response;
        if (is_array($response) && isset($response['responseCode'])) {
            switch ($response['responseCode']) {
                case 200:
                    return $response['responseData'];
                case 401:
                    return ['error' => true, 'message' => 'Token expired'];
                default:
                    return ['error' => true, 'message' => $response['responseMessage'] ?? 'Something went wrong'];
            }
        }
    
        return ['error' => true, 'message' => 'Something went wrong'];
    }
    public function getIfsc(Request $request)
    { 
        $bankCode=$request->bank_code;
        if (!$bankCode) {
            return ['error' => true, 'message' => 'Bank code is required'];
        }
        $payload = [
            'BankCode' => $bankCode
        ];
        $data = [
            'url'     => 'https://api-uat.soliteck.in/api/Payout/ApiUserGetMasterIFSCForBank',
            'payload' => json_encode($bankCode),
            'method'  => 'POST'
        ];
        $response = Soliteck::service($data);
    //  return $response;
        if (is_array($response) && isset($response['responseCode'])) {
            switch ($response['responseCode']) {
                case 200:
                     return ResponseHelper::success($response['responseMessage'], $response['responseData'], 200);
                case 401:
                   return ResponseHelper::error($response['responseMessage'], $response['responseData'], 401);
                default:
                     return ResponseHelper::error($response['responseMessage'], $response['responseData'], 403);
                    // return ['error' => true, 'message' => $response['responseMessage'] ?? 'Something went wrong'];
            }
        }
    
        return ['error' => true, 'message' => 'Something went wrong'];
    }
    public function addBeneficiary(Request $request)
    {
        $validator = SoliteckValidation::addBeneficiary($request->all());
        if ($validator->fails()) {
            return ResponseHelper::error('Validation Failed', $validator->errors(), 400);
        }
    
        $user = Auth::user();
        if (!in_array($user->role, ['dd', 'ret', 'api'])) {
            return ResponseHelper::error('Unauthorized action', [], 403);
        }
        $sender = PayoutSender::where('mobile_number', $request->mobile_number)
                ->where('pipe', "SOLITECK")
                ->first();
            
            if (!$sender) {
                return ResponseHelper::error('Sender not found ', [], 409);
            }
        // if ($request->bank_code && empty($request->bene_ifsc)) {
        //     $ifscResponse = $this->getIfscByBankCode($request->bank_code);
        //     if (is_array($ifscResponse) && isset($ifscResponse['error'])) {
        //         return ResponseHelper::error($ifscResponse['message'], [], 400);
        //     }
        //     $request->merge(['bene_ifsc' => $ifscResponse]);
        // }
       $bene = preg_replace('/[^A-Za-z0-9 ]/', '', $request->bene_name);
        $bene = preg_replace('/\s+/', ' ', $bene); 
        $bene = trim($bene);
        $payload = [
            "RemitterNumber" => (string) $request->mobile_number,
            // "BeneId"         => $request->bene_id,
            "BeneName"       => $bene,
            "BeneAccount"    => $request->bene_account,
            "BeneIFSC"       => $request->ifsc_code,
            "BankCode"       => $request->bank_code,
            "BenePincode"    => $request->bene_pincode,
            "ClientReq"      => uniqid('req_')
        ];
        $data = [
            'url'     => 'https://api-uat.soliteck.in/api/Payout/APIUserPayoutAddBeneToList',
            'payload' => json_encode($payload),
            'method'  => 'POST'
        ];
        //  return $payload;
        $response = Soliteck::service($data);
    //   return $response;
        if (empty($response) || !isset($response['responseCode'])) {
            return ResponseHelper::error('Invalid response from Soliteck API', [], 500);
        }
    
        if ($response['responseCode'] === 200 && isset($response['responseData'])) {
            $existing = PayoutBeneficiary::where('mobile_number', $request->mobile_number)
                ->where('account_number', $request->bene_account)
                ->where('ifsc_code', $request->ifsc_code)
                  ->where('type', "SOLITECK")
                ->first();
        
            if ($existing) {
                return ResponseHelper::error('Beneficiary already exists', [], 409);
            }
    
            $beneficiary = new PayoutBeneficiary();
            $beneficiary->beneficiary_name = $bene;
            $beneficiary->ifsc_code        = $request->ifsc_code;
            $beneficiary->account_number   = $request->bene_account;
            $beneficiary->bank_name        = $request->bank_name ?? '';
            $beneficiary->type             = 'SOLITECK';
            $beneficiary->soliteck_id      = $response['responseData'];
            $beneficiary->sender_id        = $sender->id; 
            $beneficiary->mobile_number    = $request->mobile_number;
            $beneficiary->is_verified      = $request->is_verified;
            $beneficiary->save();
        }
        // return $response;
        return $this->handleResponse($response);
    }
    public function deleteBeneficiary(Request $request)
    {
        $validator = SoliteckValidation::deleteBeneficiary($request->all());
        if ($validator->fails()) {
            return ResponseHelper::error('Validation Failed', $validator->errors(), 400);
        }
    
        $user = Auth::user();
        if (!in_array($user->role, ['adm', 'sadm', 'ret', 'api'])) {
            return ResponseHelper::error('Unauthorized action', [], 403);
        }
    
        $payload = [
            "RemitterNumber" => (string) $request->mobile_number,
            "BeneId"         => (string) $request->bene_id,
            "ClientReq"      => uniqid('req')
        ];
    
        $data = [
            'url'     => 'https://api-uat.soliteck.in/api/Payout/APIUserPayoutDeleteBeneFromList',
            'payload' => json_encode($payload),
            'method'  => 'POST'
        ];
    
        $response = Soliteck::service($data);
        // return $response;
        if (empty($response) || !isset($response['responseCode'])) {
            return ResponseHelper::error('Invalid response from Soliteck API', [], 500);
        }
    
        // If deleted successfully, remove from local DB
        if ($response['responseCode'] === 200) {
            PayoutBeneficiary::where('mobile_number', $request->mobile_number)
                ->where('soliteck_id', $request->bene_id)
                ->delete();
        }
    
        return $this->handleResponse($response);
    }
        /**
         * Verify Beneficiary
         */
     public function verifyBeneficiary(Request $request)
    {
        $validator = SoliteckValidation::verifyBeneficiary($request->all());
        if ($validator->fails()) {
            return ResponseHelper::error('Validation Failed', $validator->errors(), 400);
        }
    
        $user = Auth::user();
        if (!in_array($user->role, ['adm', 'sadm', 'ret', 'api'])) {
            return ResponseHelper::error('Unauthorized action', [], 403);
        }
    
        $payload = [
            "RemitterNumber" => (string)$request->mobile_number,
            "BeneId"         => (string)$request->bene_id,
            // "BeneName"       => (string)$request->bene_name,
            // "BeneAccount"    => (string)$request->bene_account,
            // "BeneIFSC"       => (string)$request->bene_ifsc,
            // "BankCode"       => (string)$request->bank_code,
            "ClientOrderId"  => (string)$request->client_order_id,
            // "ClientReq"      => (string)uniqid('req')
        ];
    //   return $payload;
        $data = [
            'url'     => 'https://api-uat.soliteck.in/api/Payout/PayoutVerifyBene',
            'payload' => json_encode($payload), 
            'method'  => 'POST'
        ];
    
        $response = Soliteck::service($data);
      return $response;
        if (empty($response) || !isset($response['responseCode'])) {
            return ResponseHelper::error('Invalid response from Soliteck API', [], 500);
        }
    
        if ($response['responseCode'] === 200) {
            PayoutBeneficiary::where('mobile_number', $request->mobile_number)
                ->where('soliteck_id', $request->bene_id)
                ->update(['is_verified' => 1]);
        }
    
        return $this->handleResponse($response);
    }
     public function payOut2(Request $request)
    { 
        $user = Auth::user();
      
        $validator = SoliteckValidation::payOut2($request->all());
        if ($validator->fails()) {
            return ResponseHelper::error('Validation failed', $validator->errors(), 422);
        }
         $dupCheck = Uuid::check($request->client_ref,'payOut2',$request);
        if($dupCheck['code'] != '200'){
            return ResponseHelper::error('Duplicate Transaction',[],402);
        }
        if (!in_array($user->role, ["api", "dd", "ret"])) {
            return ResponseHelper::error('Action not Authorised', '', 403);
        }
        $mpin=OTP::verifyMpin($request->mpin);
        if(!$mpin){
             return ResponseHelper::error("invalid mpin", [], 403);
        }
        $timeWindow = Carbon::now()->subMinutes(2);
        $duplicate = PayoutTxn::where('account_number', $request->account_number)
            ->where('amount', $request->amount)
            ->whereIn('status', ['PENDING', 'SUCCESS'])
            ->where('created_at', '>=', $timeWindow)
            ->exists();
        if ($duplicate){
            return ResponseHelper::error('Transaction With Same Amount To This Account No Is Under Process Try After 2 Minutes Or Try Changing Amount',[],429);
        }
       
        $sender = PayoutSender::where('mobile_number', $request->mobile_number)
            ->where('pipe', "SOLITECK")
            ->first();
    
        if (!$sender) {
            return ResponseHelper::error('Sender Not Found Or Unverified', '', 403);
        }
    
        if ($sender->rem_limit < $request->amount) {
            return ResponseHelper::error('Sender Limit Exceeds', '', 403);
        }
        $service = Service::find($request->operator);
        $comm = CommRule::calculateCommission($user, $service->name, $request->amount);
        if (!$comm) {
            return ResponseHelper::error('Commission Slab Not Configured', '', 403);
        }
        $a_comm = AcommRule::calculateAdminCommission($service->name, $request->amount, $service->route);
        if (!$a_comm) {
            return ResponseHelper::error('Admin Commission Slab Not Configured', '', 403);
        }
    
        if (in_array($user->role, ["api", "dd", "ret"])) {
            $comm = CommRule::finalCommission($comm, $request->amount, $service);
            $a_comm = AcommRule::finalCommission($a_comm, $request->amount);
        }
        $totalDebit = ($request->amount + $user->lien) * 100 + ($comm['ret'] ?? $comm['dd']) * 100;
        if ($user->w1 < $totalDebit) {
            return ResponseHelper::error('Insufficient W1 Balance', '', 403);
        }
        DB::beginTransaction();
        try {
            $payoutTxn = PayoutService::create($user, $service, $request, $comm, $a_comm);
            WalletService::debit($user, $request->amount, $payoutTxn, $service);
            $auditLog = AuditService::logRequest($user, $request, $service);
    
            DB::commit();
        } catch (\Exception $e) {
            DB::rollBack();
            return ResponseHelper::error('Transaction Init Failed', $e->getMessage(), 500);
        }
        $payload = [
            "RemitterNumber" => (string) $request->mobile_number,
            "BeneId"         => (string) $request->soliteck_id,
            "Amount"         => (string) $request->amount,         
            "ClientOrderId"  => (string) $payoutTxn->txn_id, 
            "Mode"           => $request->mop
        ];
    
        $data = [
            'url'     => 'https://api-uat.soliteck.in/api/Payout/PayoutTransfer',
            'payload' => json_encode($payload),
            'method'  => 'POST'
        ];
    
        $response = Soliteck::service($data);
        DB::beginTransaction();
        try {
            $result = PayoutResponseHandler::handleResponse($payoutTxn, $response, $user, $service, $comm, $sender);
            AuditService::updateResponse($auditLog, $response);
    
            DB::commit();
    
            return $result;
        } catch (\Exception $e) {
            DB::rollBack();
            return ResponseHelper::error('Transaction Update Failed', $e->getMessage(), 500);
        }
    }
    private function handleResponse($response)
    {
        if (!is_array($response) || !isset($response['responseCode'])) {
            return ResponseHelper::error('Something went wrong', [], 400);
        }

        switch ($response['responseCode']) {
            case 200:
            case 201:
            case 204:
                $data = isset($response['responseData']) ? json_decode($response['responseData'], true) : null;
                return ResponseHelper::success($response['responseMessage'], $data, $response['responseCode']);

            case 401:
                return ResponseHelper::error($response['responseMessage'] ?? 'Token expired', [], 401);

            default:
                return ResponseHelper::error($response['responseMessage'] ?? 'Something1 went wrong', [], 400);
        }
    }
    
   public function checkTransactionStatus(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'txn_id' => 'required|string',
        ]);

        if ($validator->fails()) {
            return ResponseHelper::error($validator->errors(), [], 400);
        }

        $payload = json_encode($request->input('txn_id'));

        $data = [
            'url'     => 'https://api-uat.soliteck.in/api/AdminActivity/CheckTxnStatus',
            'payload' => $payload,
            'method'  => 'POST'
        ];

        $response = Soliteck::service($data);
    //   return $response;
        if (!is_array($response) || !isset($response['responseCode'])) {
            return ResponseHelper::error('Something went wrong', [], 400);
        }

        $responseData = isset($response['responseData']) ? json_decode($response['responseData'], true) : null;

        $statusMap = [
            1 => 'Success',
            0 => 'Failed',
            3 => 'Pending',
        ];
        $status=$statusMap[$responseData[0]["StatusId"]];

     
            return ResponseHelper::success(
                $response['responseMessage'],
                $status,
                200
            );
       
    }


    /**
     * Webhook Handler
     */
    public function webhook(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'client_order_id' => 'required|string',
            'utr'             => 'required|string',
            'status_code'     => 'required|in:200,204', // 200=Success, 204=Failed
            'message'         => 'required|string'
        ]);

        if ($validator->fails()) {
            return ResponseHelper::error($validator->errors(), [], 400);
        }

        $data = $request->only(['client_order_id','utr','status_code','message']);

        // You can perform further business logic here
        // e.g., update txn in database based on client_order_id

        if ($data['status_code'] == 200) {
            return ResponseHelper::success('Transaction Success', $data, 200);
        } else {
            return ResponseHelper::error('Transaction Failed', $data, 204);
        }
    }
    
}  
    

