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
use App\Modules\Dmt\Services\DmtService; 
use App\Modules\Integration\Models\Utkal;
use App\Modules\Integration\Models\Abhayam;
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
use App\Modules\Payout\Models\BeneficiaryValidation;
use Illuminate\Support\Facades\Log;

class UtkalController extends BaseController
{
    use ApiResponser;

    public function __construct(){
        $this->middleware('auth', ['except' => ['']]);
        $this->middleware('check.active', ['except' => '']);
        $this->middleware('check.whitelist', ['except' => 'getPayoutTxns','getPayoutSchema','upiVerifiaction','createPurpose','getPurpose']);
    }
    public function getPayoutSchema(){
        $schema=PayoutValidation::getPayoutSchema();
        return ResponseHelper::success('Schema fetched', $schema, 200);
    }
    public function getSender5(Request $request) {
        $user = Auth::user();
//  return $request->root();
        // Role validation
        if (!in_array($user->role, ["ret", "dd", "api"])) {
            return ResponseHelper::error('Unauthorised Action', '', 403);
        }
    
        $mobile = $request->input('mobile_number');
        if (!$mobile) {
            return ResponseHelper::error('Mobile number required', '', 403);
        }
    
        // 1) Check DB first - if found, return immediately (no API calls)
        $existingSender = PayoutSender::with(['beneficiary' => function ($q) {
            $q->where('type', 'UTKAL');
        }])->where('mobile_number', $mobile)
          ->where('pipe', 'UTKAL')->where('is_active', 1)
          ->first();
    
        if ($existingSender) {
            return ResponseHelper::success('Success', $existingSender, 200);
        }
    
        // 2) Not in DB -> call ABHAYAM Sender Check API
        $payload = [
            "Authcode"     => env("UTKAL_PAY_KEY"),
            "Mpin"         => env("UTKAL_PAY_MPIN"),
            "SenderMobile" => $mobile,
            "RequestID"    => (string) uniqid('req_'),
        ];
    
        $data = [
            'url'     => 'https://payoutsender.utkaldigital.co.in/ProcessRequest/Check',
            'payload' => json_encode($payload),
            'method'  => 'POST'
        ];
    
        $response = Utkal::service($data);
        //   return $response;
        // If ABHAYAM says not found or any failure, reply with Sender Not Found
        if (!isset($response['Status']) || $response['Status'] !== "Success") {
            return ResponseHelper::success('Sender Not Found', '', 200);
        }
    
        // 3) Save new sender (or update) after successful API response
        $senderName = $response['SenderName'] ?? '';
        $sender = PayoutSender::firstOrNew([
            'mobile_number' => $mobile,
            'pipe'          => 'UTKAL',
        ]);
    
        $sender->sender_name = $senderName;
        $sender->user_id = $user->id;
        $sender->kyc_status  = 'verified';
        $sender->rem_limit   = $response['SenderBalance'] ?? null;
        $sender->is_active   = 1;
        $sender->save();
    
        // 4) Fetch beneficiaries from ABHAYAM API
        $benePayload = [
            "Authcode"     => env("UTKAL_PAY_KEY"),
            "Mpin"         => env("UTKAL_PAY_MPIN"),
            "SenderMobile" => $mobile,
            "RequestID"    => (string) uniqid('req_')
        ];
    
        $beneData = [
            "url"     => "https://payoutbeneficiary.utkaldigital.co.in/ProcessRequest/GetDetails=",
            "payload" => json_encode($benePayload),
            "method"  => "POST"
        ];
    
        $beneResponse = Utkal::service($beneData);
    //   return $beneResponse;
        if (isset($beneResponse["Status"]) && $beneResponse["Status"] === "Success" && !empty($beneResponse["Data"])) {
            foreach ($beneResponse["Data"] as $bene) {
                // Use firstOrNew by unique keys and then update fields
                $beneficiary = PayoutBeneficiary::firstOrNew([
                    "sender_id"      => $sender->id,
                    "account_number" => $bene["AccountNo"],
                    "type"           => "UTKAL"
                ]);
    
                $beneficiary->beneficiary_name  = $bene["beneficiary_name"] ?? null;
                $beneficiary->ifsc_code         = $bene["Ifsc"] ?? null;
                $beneficiary->bank_name         = $bene["BankName"] ?? null;
                $beneficiary->api_id            = $bene["BeneficiaryId"] ?? null;
                $beneficiary->mobile_number     = $mobile;
                // $beneficiary->is_verified       = (isset($bene["isVerified"]) && $bene["isVerified"] == "True") ? 1 : 0;
                $beneficiary->save();
            }
        }
    
        // 5) Return fresh sender with beneficiaries
        $senderWithBens = PayoutSender::with(['beneficiary' => function ($query) {
            $query->where('type', 'UTKAL');
        }])->where('id', $sender->id)->first();
    
        return ResponseHelper::success('Success', $senderWithBens, 200);
    }
    public function getSenderUpi2(Request $request){
        $user = Auth::user();
//  return $request->root();
        // Role validation
        if (!in_array($user->role, ["ret", "dd", "api"])) {
            return ResponseHelper::error('Unauthorised Action', '', 403);
        }
    
        $mobile = $request->input('mobile_number');
        if (!$mobile) {
            return ResponseHelper::error('Mobile number required', '', 403);
        }
    
        // 1) Check DB first - if found, return immediately (no API calls)
        $existingSender = PayoutSender::with(['beneficiary' => function ($q) {
            $q->where('type', 'UTKALUPI');
        }])->where('mobile_number', $mobile)
          ->where('pipe', 'UTKAL')->where('is_active', 1)
          ->first();
    
        if ($existingSender) {
            return ResponseHelper::success('Success', $existingSender, 200);
        }
    
        // 2) Not in DB -> call ABHAYAM Sender Check API
        $payload = [
            "Authcode"     => env("UTKAL_PAY_KEY"),
            "Mpin"         => env("UTKAL_PAY_MPIN"),
            "SenderMobile" => $mobile,
            "RequestID"    => (string) uniqid('req_'),
        ];
    
        $data = [
            'url'     => 'https://payoutsender.utkaldigital.co.in/ProcessRequest/Check',
            'payload' => json_encode($payload),
            'method'  => 'POST'
        ];
    
        $response = Utkal::service($data);
        //   return $response;
        // If ABHAYAM says not found or any failure, reply with Sender Not Found
        if (!isset($response['Status']) || $response['Status'] !== "Success") {
            return ResponseHelper::success('Sender Not Found', '', 200);
        }
    
        // 3) Save new sender (or update) after successful API response
        $senderName = $response['SenderName'] ?? '';
        $sender = PayoutSender::firstOrNew([
            'mobile_number' => $mobile,
            'pipe'          => 'UTKAL',
        ]);
    
        $sender->sender_name = $senderName;
        $sender->user_id = $user->id;
        $sender->kyc_status  = 'verified';
        $sender->rem_limit   = $response['SenderBalance'] ?? null;
        $sender->is_active   = 1;
        $sender->save();
        $senderWithBens = PayoutSender::with(['beneficiary' => function ($query) {
            $query->where('type', 'UTKALUPI');
        }])->where('id', $sender->id)->first();
    
        return ResponseHelper::success('Success', $senderWithBens, 200);
    }
    public function registerRemitter4(Request $request){
        $user = Auth::user();
    
        $validator = Validator::make($request->all(), [
            'mobile_number' => 'required|digits:10',
            'name'          => 'required|string|max:50',
            // 'aadhar'        => 'required|string|max:12',
            // 'pan'           => 'required|string|max:50',
        ]);
    
        if ($validator->fails()) {
            return ResponseHelper::error($validator->errors(), [], 400);
        }
    
        // Clean name
        $f_name = preg_replace('/[^a-zA-Z\s]/', '', preg_replace('/\s+/', ' ', trim($request->name)));
    
        // API Payload
        $payload = [
            "Authcode"      => env("UTKAL_PAY_KEY"),
            "Mpin"          => env("UTKAL_PAY_MPIN"),
            "SenderMobile"  => $request->mobile_number,
            "SenderName"    => $f_name,
            "RequestID"     => (string) uniqid('req_'),
        ];
    
        $data = [
            'url'     => 'https://payoutsender.utkaldigital.co.in/ProcessRequest/Register',
            'payload' => json_encode($payload),
            'method'  => 'POST'
        ];
    
        $response = Utkal::service($data);
    
        if (!is_array($response) || !isset($response['Status'])) {
            return ResponseHelper::error('Something went wrong', [], 400);
        }
    
        switch ($response['Status']) {
    
            case "Success":
                $sender = PayoutSender::where('mobile_number', $request->mobile_number)
                            ->where('pipe', 'UTKAL')
                            ->first();
    
                if ($sender) {
                    $sender->sender_name = $response['SenderName'] ?? $f_name;
                    $sender->aadhar      = $request->aadhar;
                    $sender->pan         = $request->pan;
                    $sender->kyc_status  = "pending";
                    $sender->is_active   = 0;
                    $sender->user_id     = $user->id;
                    $sender->save();
                } else {
                    $sender = new PayoutSender();
                    $sender->sender_name   = $response['SenderName'] ?? $f_name;
                    $sender->mobile_number = $request->mobile_number;
                    $sender->pipe          = "UTKAL";
                    $sender->aadhar        = $request->aadhar;
                    $sender->pan           = $request->pan;
                    $sender->kyc_status    = "pending";
                    $sender->is_active     = 0;
                    $sender->user_id       = $user->id;
                    $sender->save();
                }
    
                return ResponseHelper::success(
                    $response['Description'] ?? "OTP sent successfully",
                    $response['ReferenceId'],
                    200
                );
    
            case "Failed":
                return ResponseHelper::error($response['Description'] ?? 'Failed', [], 400);
    
            default:
                return ResponseHelper::error('Something went wrong', [], 400);
        }
    }
    public function upiVerification(Request $request){
        $user = Auth::user();
        $validator = SoliteckValidation::upiVerification($request->all());
        if ($validator->fails()) {
            return ResponseHelper::error('Validation error', $validator->errors(), 422);
        }
        //   $dupCheck = Uuid::check($request->client_ref,'accountVerification1',$request);
        // if($dupCheck['code'] != '200'){
        //     return ResponseHelper::error('Duplicate Transaction',[],402);
        // }
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
            $payoutTxn = DmtService::createVerification($user, $service, $request, $comm,$a_comm);
            WalletService::debit($user, 1, $payoutTxn, $service);
            $auditLog = AuditService::logRequest($user, $request, $service);
            DB::commit();
        } catch (\Exception $e) {
            DB::rollBack();
            return ResponseHelper::error('Verification Init Failed', $e->getMessage(), 500);
        }
        $payload = [
        "Authcode"      => env("UTKAL_PAY_KEY"),
        "Mpin"          => env("UTKAL_PAY_MPIN"),
        "RequestID"     => (string)$payoutTxn->txn_id,
        "ServiceId"     => 36,
        "VPA"           => (string)$request->account_number
    ];

    $data = [
        'url'     => 'https://upipayout.utkaldigital.co.in/ProcessRequest/verify',
        'payload' => json_encode($payload),
        'method'  => 'POST'
    ];

    $response = Utkal::service($data);
    // return $response;
    DB::beginTransaction();
    try {
        $result = PayoutResponseHandler::handleUpiVerification($payoutTxn, $response, $user, $service, $comm );
        AuditService::updateResponse($auditLog, $response);

        DB::commit();
        return $result;
    } catch (\Exception $e) {
        DB::rollBack();
        return ResponseHelper::error('Transaction Update Failed', $e->getMessage(), 500);
    }
}
    public function verifyRemitterLogin4(Request $request) {
        $validator = SenderValidation::verifySender($request->all());
        if ($validator->fails()) {
            return ResponseHelper::error('Validation failed', $validator->errors(), 422);
        }
    
        $user = Auth::user();
    
        if (!in_array($user->role, ['ret', 'dd', 'api'])) {
            return ResponseHelper::error('Unauthorized Action', null, 403);
        }
    
        // 🔹 Log Incoming Request
        Log::info("UTKAL Verify Sender Request Received", [
            'url'     => $request->fullUrl(),
            'method'  => $request->method(),
            'headers' => $request->headers->all(),
            'body'    => $request->all(),
            'user_id' => $user->id ?? null,
        ]);
    
        $sender = PayoutSender::where('mobile_number', $request->mobile_number)
                    ->where('pipe', "UTKAL")
                    ->first();
    
        if (!$sender) {
            return ResponseHelper::error('Sender not found', null, 404);
        }
    
        // Build Verify Sender Payload
        $payload = [
            "Authcode"     => env("UTKAL_PAY_KEY"),
            "Mpin"         => env("UTKAL_PAY_MPIN"),
            "ReferenceId"  => $request->otp_ref,
            "OTP"          => $request->otp,
            "RequestID"    => (string) uniqid('req_'),
        ];
    
        $data = [
            'url'     => 'https://payoutsender.utkaldigital.co.in/ProcessRequest/Verify',
            'payload' => json_encode($payload),
            'method'  => 'POST'
        ];
    
        // 🔹 Log Outgoing API Request
        Log::info("UTKAL Verify Sender API Call", [
            'api_url' => $data['url'],
            'payload' => $payload,
            'base_url' => url('/'),
        ]);
    
        $response = Utkal::service($data);
    
        // 🔹 Log API Response
        Log::info("UTKAL Verify Sender API Response", [
            'response' => $response
        ]);
    
        if (!is_array($response) || !isset($response['Status'])) {
            return ResponseHelper::error('Invalid API response', $response, 400);
        }
    
        if ($response['Status'] == "Success") {
    
            $sender->kyc_status = 'verified';
            $sender->is_active  = 1;
            $sender->save();
    
            return ResponseHelper::success(
                $response['Description'] ?? 'Sender Verified Successfully',
                $response,
                200
            );
        }
    
        return ResponseHelper::error(
            $response['Description'] ?? 'Verification Failed',
            $response,
            400
        );
    }
    public function getUtkalBanks(Request $request){
        $user = Auth::user();
        if (!in_array($user->role, ["ret", "dd", "api"])) {
            return ResponseHelper::error('Unauthorized Action', '', 403);
        }
        $payload = [
            "Authcode"  => env("UTKAL_PAY_KEY"),
            "Mpin"      => env("UTKAL_PAY_MPIN"),
            "RequestID" => (string) uniqid('req_'),
        ];
    
        $data = [
            'url'     => 'https://payoutbeneficiary.utkaldigital.co.in/ProcessRequest/GetBankDetails',
            'payload' => json_encode($payload),
            'method'  => 'POST'
        ];
    
        // Call Abhayam API
        $response = Utkal::service($data);
    
        // Validate API response
        if (!isset($response['Status'])) {
            return ResponseHelper::error('Invalid response from UTKAL API', [], 500);
        }
    
        if ($response['Status'] !== "Success") {
            return ResponseHelper::error($response['Description'] ?? 'Failed', [], 400);
        }
    
        // Extract bank list
        $bankList = $response['Data'] ?? [];
    
        return ResponseHelper::success("Bank List Fetched Successfully", $bankList, 200);
    }
    public function addBeneficiaryUtkal(Request $request){
        $validator = Validator::make($request->all(), [
            'mobile_number' => 'required|digits:10',
            'bene_name'     => 'required|string|max:80',
            'bene_account'  => 'required|numeric',
            'ifsc_code'     => 'required|string|max:15',
            'bank_name'     => 'required|string|max:150',
            'bank_code'     => 'required|string|max:20'
        ]);
    
        if ($validator->fails()) {
            return ResponseHelper::error('Validation Failed', $validator->errors(), 422);
        }
        $user = Auth::user();
        if (!in_array($user->role, ['dd', 'ret', 'api'])) {
            return ResponseHelper::error('Unauthorized action', [], 403);
        }
        $sender = PayoutSender::where('mobile_number', $request->mobile_number)
            ->where('pipe', "UTKAL")->first();
    
        if (!$sender) {
            return ResponseHelper::error('Sender not found', [], 409);
        }
        $bene = preg_replace('/[^A-Za-z0-9 ]/', '', $request->bene_name);
        $bene = preg_replace('/\s+/', ' ', $bene);
        $bene = trim($bene);
        $payload = [
            "Authcode"        => env("UTKAL_PAY_KEY"),
            "Mpin"            => env("UTKAL_PAY_MPIN"),
            "SenderMobile"    => $request->mobile_number,
            "BeneficiaryName" => $bene,
            "AccountNo"       => $request->bene_account,
            "Ifsc"            => strtoupper($request->ifsc_code),
            "BankCode"        => strtoupper($request->bank_code),
            "BankName"        => $request->bank_name,
            "RequestID"       => uniqid('req_'),
            "Rfu1"            => "",
            "Rfu2"            => "",
            "Rfu3"            => ""
        ];
    
        $data = [
            'url'     => 'https://payoutbeneficiary.utkaldigital.co.in/ProcessRequest/Add',
            'payload' => json_encode($payload),
            'method'  => 'POST'
        ];
    
        $response = Utkal::service($data);
        // return $response;
        if (!isset($response['Status'])) {
            return ResponseHelper::error('Invalid response from UTKAL API', [], 500);
        }
    
        if ($response['Status'] !== "Success") {
            return ResponseHelper::error($response['Description'] ?? 'Failed', [], 400);
        }
        $existing = PayoutBeneficiary::where('mobile_number', $request->mobile_number)
            ->where('account_number', $request->bene_account)
            ->where('ifsc_code', $request->ifsc_code)
            ->where('type', "UTKAL")
            ->first();
    
        if ($existing) {
            return ResponseHelper::error('Beneficiary already exists', [], 409);
        }
        $beneficiary = new PayoutBeneficiary();
        $beneficiary->beneficiary_name = $bene;
        $beneficiary->ifsc_code        = strtoupper($request->ifsc_code);
        $beneficiary->account_number   = $request->bene_account;
        $beneficiary->bank_name        = $request->bank_name;
        $beneficiary->type             = 'UTKAL';
        $beneficiary->api_id       = $response['BenficiaryId'] ?? null;
        $beneficiary->sender_id        = $sender->id;
        $beneficiary->mobile_number    = $request->mobile_number;
        $beneficiary->is_verified      = $request->is_verified;
        $beneficiary->save();
    
        return ResponseHelper::success('Beneficiary Added Successfully', $beneficiary, 200);
    }
    public function deleteBeneficiaryUtkal(Request $request){
        $validator = Validator::make($request->all(), [
            'mobile_number' => 'required|digits:10',
            'bene_id'       => 'required'
        ]);
    
        if ($validator->fails()) {
            return ResponseHelper::error('Validation Failed', $validator->errors(), 400);
        }
        $user = Auth::user();
        if (!in_array($user->role, ['adm', 'sadm', 'ret', 'api'])) {
            return ResponseHelper::error('Unauthorized action', [], 403);
        }
        $payload = [
            "Authcode"      => env("UTKAL_PAY_KEY"),
            "Mpin"          => env("UTKAL_PAY_MPIN"),
            "SenderMobile"  => (string) $request->mobile_number,
            "RequestID"     => uniqid('req_'),
            "BeneficiaryId" => (string) $request->bene_id,
        ];
        $data = [
            'url'     => 'https://payoutbeneficiary.utkaldigital.co.in/ProcessRequest/Delete',
            'payload' => json_encode($payload),
            'method'  => 'POST'
        ];
        $response = Utkal::service($data);
        if (empty($response) || !isset($response['Status'])) {
            return ResponseHelper::error('Invalid response from UTKAL API', [], 500);
        }
        if ($response['Status'] !== "Success") {
            return ResponseHelper::error($response['Description'] ?? 'Failed', [], 400);
        }
        PayoutBeneficiary::where('mobile_number', $request->mobile_number)
            ->where('api_id', $request->bene_id) 
            ->delete();
        return ResponseHelper::success("Beneficiary Deleted Successfully", [], 200);
    }
    public function verifyBeneficiary(Request $request){
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
    public function payOutUtkal(Request $request){ 
            $user = Auth::user();
       
            // Validation
           $validator = SoliteckValidation::payOutAbhayam($request->all());
        if ($validator->fails()) {
            return ResponseHelper::error('Validation failed', $validator->errors(), 422);
        }
            // Duplicate check
            $dupCheck = Uuid::check($request->client_ref, 'payOutUtkal', $request);
            if ($dupCheck['code'] != '200') {
                return ResponseHelper::error('Duplicate Transaction', [], 402);
            }
        
            // Role check
            if (!in_array($user->role, ["api", "dd", "ret"])) {
                return ResponseHelper::error('Action not Authorised', '', 403);
            }
        
            // Verify Mpin
            $mpin = OTP::verifyMpin($request->mpin);
            if (!$mpin) {
                return ResponseHelper::error("invalid mpin", [], 403);
            }
        
            // Transaction duplicate block check
            $timeWindow = Carbon::now()->subMinutes(2);
            $duplicate = PayoutTxn::where('account_number', $request->account_number)
                ->where('amount', $request->amount)
                ->whereIn('status', ['PENDING', 'SUCCESS'])
                ->where('created_at', '>=', $timeWindow)
                ->exists();
        
            if ($duplicate) {
                return ResponseHelper::error(
                    'Transaction With Same Amount To This Account No Is Under Process Try After 2 Minutes Or Try Changing Amount',
                    [],
                    429
                );
            }

            $sender = PayoutSender::where('mobile_number', $request->mobile_number)
                ->where('pipe', "UTKAL")
                ->first();
        
            if (!$sender) {
                return ResponseHelper::error('Sender Not Found Or Unverified', '', 403);
            }
        
            if ($sender->rem_limit < $request->amount) {
                return ResponseHelper::error('Sender Limit Exceeds', '', 403);
            }
        
            // Commission
            $service = Service::find($request->operator);
             if(!$service){
            return ResponseHelper::error('Service not Configured ', '', 403);
        }
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
        
            // ABHAYAM PAYOUT PAYLOAD
            $payload = [
                "Authcode"      => env("UTKAL_PAY_KEY"),
                "Mpin"          => env("UTKAL_PAY_MPIN"),
                "RequestID"     => (string)$payoutTxn->txn_id,
                "ServiceId"     => 27,
                "SenderMobile"  => (string)$request->mobile_number,
                "BeneficiaryId" => (string)$request->bene_id,
                "Amount"        => (string)$request->amount,
                "AdharNo"       => '123123123123',
                "PanNo"         => (string)$user->pan,
                 "Lat"           => (string)$request->latitude??22.753,
                "Long"          => (string)$request->longitude??78.773
            ];
        
            $data = [
                'url'     => 'https://payoutransaction.utkaldigital.co.in/ProcessRequest/transaction',
                'payload' => json_encode($payload),
                'method'  => 'POST'
            ];
        
            $response = Utkal::service($data);
        
            DB::beginTransaction();
            try {
                $result = PayoutResponseHandler::handleResponseAbhyam($payoutTxn, $response, $user, $service, $comm, $sender);
                AuditService::updateResponse($auditLog, $response);
        
                DB::commit();
        
                return $result;
            } catch (\Exception $e) {
                DB::rollBack();
                return ResponseHelper::error('Transaction Update Failed', $e->getMessage(), 500);
            }
        }
    public function upiUtkal(Request $request){ 
    $user = Auth::user();
//  return $request->all();
    // Validation
    $validator = SoliteckValidation::upiUtkal($request->all());
    if ($validator->fails()) {
        return ResponseHelper::error('Validation failed', $validator->errors(), 422);
    }

    // Duplicate check
    $dupCheck = Uuid::check($request->client_ref, 'payOutUtkal', $request);
    if ($dupCheck['code'] != '200') {
        return ResponseHelper::error('Duplicate Transaction', [], 402);
    }

    // Role check
    if (!in_array($user->role, ["api", "dd", "ret"])) {
        return ResponseHelper::error('Action not Authorised', '', 403);
    }

    // Verify Mpin
    $mpin = OTP::verifyMpin($request->mpin);
    if (!$mpin) {
        return ResponseHelper::error("invalid mpin", [], 403);
    }

    // Transaction duplicate block check
    $timeWindow = Carbon::now()->subMinutes(2);
    $duplicate = PayoutTxn::where('account_number', $request->account_number)
        ->where('amount', $request->amount)
        ->whereIn('status', ['PENDING', 'SUCCESS'])
        ->where('created_at', '>=', $timeWindow)
        ->exists();

    if ($duplicate) {
        return ResponseHelper::error(
            'Transaction With Same Amount To This Account No Is Under Process Try After 2 Minutes Or Try Changing Amount',
            [],
            429
        );
    }

    $sender = PayoutSender::where('mobile_number', $request->mobile_number)
    ->where('pipe', 'UTKAL')
    ->first();
    if (!$sender) {
        $sender = PayoutSender::where('mobile_number', $request->mobile_number)
            ->where('pipe', 'ABHAYAM')
            ->first();
    }
    
    // If sender not found in both
    if (!$sender) {
        return ResponseHelper::error('Sender Not Found Or Unverified', '', 403);
    }
    
    // Check sender limit
    if ($sender->rem_limit < $request->amount) {
        return ResponseHelper::error('Sender Limit Exceeds', '', 403);
    }


    // Commission
    $service = Service::find($request->operator);
   
    if (!$service) {
        return ResponseHelper::error('Service not Configured ', '', 403);
    }
    if (!in_array($service->route, ['UTKAL', 'ABHAYAM'])) {
    return ResponseHelper::error('Invalid Route', '', 403);
     }


    $comm = CommRule::calculateCommission($user, $service->name, $request->amount);
    //  return $comm;
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

    if($service->route=="UTKAL"){
    $payload = [
        "Authcode"      => env("UTKAL_PAY_KEY"),
        "Mpin"          => env("UTKAL_PAY_MPIN"),
        "RequestID"     => (string)$payoutTxn->txn_id,
        "ServiceId"     => 37,
        "VPA"           => (string)$request->account_number,
        "Amount"        => (string)$request->amount,
        "MobileNumber"  => (string)$request->mobile_number,
        "Name"          => (string)$request->beneficiary_name,
        "Lat"           => (string)($request->latitude ?? 22.753),
        "Long"          => (string)($request->longitude ?? 78.773),
        "EmailId"       => (string)($request->email ?? "info@test.com"),
        "Address"       => (string)($request->address ?? "India")
    ];

    $data = [
        'url'     => 'https://upipayout.utkaldigital.co.in/ProcessRequest/transaction',
        'payload' => json_encode($payload),
        'method'  => 'POST'
    ];

    $response = Utkal::service($data);
     }else{
        
    $payload = [
        "Authcode"      => env("ABHAYAM_PAY_KEY"),
        "Mpin"          => env("ABHAYAM_PAY_MPIN"),
        "RequestID"     => (string)$payoutTxn->txn_id,
        "ServiceId"     => 37,
        "VPA"           => (string)$request->account_number,
        "Amount"        => (string)$request->amount,
        "MobileNumber"  => (string)$request->mobile_number,
        "Name"          => (string)$request->beneficiary_name,
        "Lat"           => (string)($request->latitude ?? 22.753),
        "Long"          => (string)($request->longitude ?? 78.773),
        "EmailId"       => (string)($request->email ?? "info@test.com"),
        "Address"       => (string)($request->address ?? "India")
    ];

    $data = [
        'url'     => 'https://upitxn.abhayampay.in/ProcessRequest/transaction',
        'payload' => json_encode($payload),
        'method'  => 'POST'
    ];

    $response = Abhayam::service($data);

     }
    DB::beginTransaction();
    try {
        $result = PayoutResponseHandler::handleResponseAbhyam($payoutTxn, $response, $user, $service, $comm, $sender);
        AuditService::updateResponse($auditLog, $response);

        DB::commit();
        return $result;
    } catch (\Exception $e) {
        DB::rollBack();
        return ResponseHelper::error('Transaction Update Failed', $e->getMessage(), 500);
    }
}
    public function createBeneficiaryUpi2(Request $request){
        $validator = BeneficiaryValidation::createBeneficiary($request->all());
        if ($validator->fails()) {
            return ResponseHelper::error("Validation error", $validator->errors(), 422);
        }
        $name = preg_replace('/[^A-Za-z\s]/', ' ', $request->beneficiary_name);
        $name = preg_replace('/\s+/', ' ', $name);
        $beneficiaryName = ucwords(trim($name));
        $beneficiary = new PayoutBeneficiary();
        $beneficiary->sender_id        = $request->sender_id;
        $beneficiary->beneficiary_name = $beneficiaryName;
        $beneficiary->account_number   = $request->account_number;
        $beneficiary->ifsc_code        = $request->ifsc_code;
        $beneficiary->type             = 'UTKALUPI';
        $beneficiary->bank_name        = $request->bank_name;
        $beneficiary->mobile_number    = $request->mobile_number;
        $beneficiary->is_verified      = $request->is_verified ?? 0;
    
        if ($beneficiary->save()) {
            return ResponseHelper::success('Beneficiary created successfully', $beneficiary, 200);
        } else {
            return ResponseHelper::error('Beneficiary could not be created', [], 403);
        }
    }
    private function handleResponse($response){
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
    public function checkTransactionStatus(Request $request){
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
    public function webhook(Request $request){
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
    public function getAllVpa(Request $request){
        $user = Auth::user();
    
        if (!in_array($user->role, ['adm', 'sadm', 'ret', 'dd', 'api'])) {
            return ResponseHelper::error('Unauthorized', [], 403);
        }
    
        $query = PayoutBeneficiary::where('type', 'UTKALUPI')
            ->select('id', 'sender_id', 'account_number as vpa', 'is_verified', 'created_at');
    
        if ($request->filled('sender_id')) {
            $query->where('sender_id', $request->sender_id);
        }
    
        $vpas = $query->orderBy('id', 'desc')->get();
    
        return ResponseHelper::success('VPA list fetched', $vpas, 200);
    }
    public function initiateDynamicQrCollection111(Request $request){
        $validator = Validator::make($request->all(), [
            // 'service_id'      => 'required',
            // 'name'            => 'required|string',
            // 'merchant_mobile' => 'required|digits:10',
            'email'           => 'required|email',
            'amount'          => 'required|numeric|min:1',
            'latitude'        => 'required',
            'longitude'       => 'required',
        ]);
    
        if ($validator->fails()) {
            return ResponseHelper::error('Validation failed', $validator->errors(), 422);
        }
          $collection = new Collection;
                $collection->user_id = $user->id;
                $collection->rrn = $data['rrn'];
                $collection->refId = $data['refId'];
                $collection->payerAddress = $data['payerAddress'];
                $collection->payerName = $data['payerName'];
                $collection->payerACNo = $data['payerACNo'];
                $collection->payerACIFSCCode = $data['payerACIFSCCode'];
                $collection->amount = $data['amount'];
                $collection->status = 'Unclaimed';
                $collection->payeeAddress = $data['payeeAddress'];
                $collection->save();
        $user = Auth::user();
    
        if (!in_array($user->role, ['ret', 'dd', 'api'])) {
            return ResponseHelper::error('Unauthorized Action', null, 403);
        }
        $requestId = (string) uniqid('QR_');
    
        $payload = [
            "Authcode"       => env("UTKAL_PAY_KEY"),
            "Mpin"           => env("UTKAL_PAY_MPIN"),
            "RequestID"      => $requestId,
            "ServiceId"      => "38",
            "Name"           => $user->establishment,
            "MerchantMobile" => $user->mobile,
            "Emailid"        => $request->email,
            "Amount"         =>$request->amount,
            "Latitude"       => $request->latitude,
            "Longitude"      => $request->longitude
        ];
    
        $data = [
            'url'     => 'https://qrcollection.utkaldigital.co.in/ProcessRequest/initiate',
            'payload' => json_encode($payload),
            'method'  => 'POST'
        ];
    
        // 🔹 Log Request
        Log::info("UTKAL Dynamic QR Request", [
            'api_url' => $data['url'],
            'payload' => $payload,
            'user_id' => $user->id ?? null,
        ]);
    
        $response = Utkal::service($data);
    
        // 🔹 Log Response
        Log::info("UTKAL Dynamic QR Response", [
            'response' => $response
        ]);
    
        if (!is_array($response) || !isset($response['Status'])) {
            return ResponseHelper::error('Invalid API response', $response, 400);
        }
    
        if (in_array($response['Status'], ['Pending', 'Success'])) {
    
            return ResponseHelper::success(
                $response['Description'] ?? 'QR Generated Successfully',
                $response,
                200
            );
        }
    
        return ResponseHelper::error(
            $response['Description'] ?? 'QR Generation Failed',
            $response,
            400
        );
    }
    public function initiateDynamicQrCollection(Request $request){
        $user = Auth::user();
    
        $validator = Validator::make($request->all(), [
            'email'     => 'required|email',
            'amount'    => 'required|numeric|min:1',
            'latitude'  => 'required',
            'longitude' => 'required',
        ]);
    
        if ($validator->fails()) {
            return ResponseHelper::error('Validation failed', $validator->errors(), 422);
        }
    
        if (!in_array($user->role, ["api", "dd", "ret"])) {
            return ResponseHelper::error('Action not Authorised', '', 403);
        }
    
        $service = Service::find(366);
        if (!$service) {
            return ResponseHelper::error('Service not Configured', '', 403);
        }
    
        $comm = CommRule::calculateCommission($user, $service->name, $request->amount);
        if (!$comm) {
            return ResponseHelper::error('Commission Slab Not Configured', '', 403);
        }
    
        $a_comm = AcommRule::calculateAdminCommission($service->name, $request->amount, $service->route);
        if (!$a_comm) {
            return ResponseHelper::error('Admin Commission Slab Not Configured', '', 403);
        }
    
        $comm   = CommRule::finalCommission($comm, $request->amount, $service);
        $a_comm = AcommRule::finalCommission($a_comm, $request->amount);
    
        DB::beginTransaction();
        try {
            $payoutTxn = PayoutService::createCollection($user, $service, $request, $comm, $a_comm);
            $auditLog  = AuditService::logRequest($user, $request, $service);
            DB::commit();
        } catch (\Exception $e) {
            DB::rollBack();
            return ResponseHelper::error('Transaction Init Failed', $e->getMessage(), 500);
        }
    
                $payload = [
                "Authcode"       => env("UTKAL_PAY_KEY"),
                "Mpin"           => env("UTKAL_PAY_MPIN"),
                "RequestID"      => $payoutTxn->txn_id,
                "ServiceId"      => "38",
                "Name"           => $user->establishment,
                "MerchantMobile" => $user->mobile,
                "Emailid"        => $request->email,
                "Amount"         =>$request->amount,
                "Latitude"       => $request->latitude,
                "Longitude"      => $request->longitude
            ];
        
            $data = [
                'url'     => 'https://qrcollection.utkaldigital.co.in/ProcessRequest/initiate',
                'payload' => json_encode($payload),
                'method'  => 'POST'
            ];
        //   return $data;
        $apiResponse = Utkal::service($data);
        $response    = $apiResponse;
    
        DB::beginTransaction();
        try {
    
            AuditService::updateResponse($auditLog, $apiResponse);
    
            if (!isset($response['Status'])) {
                DB::commit();
                return ResponseHelper::error('Invalid API Response', $response, 500);
            }
    
            if ($response['Status'] == "Pending") {
    
                $payoutTxn->update([
                    // 'vendor_txn'  => $response['TransId'] ?? null,
                    'qr_string'   => $response['QRString'] ?? null,
                    'api_response'=> $apiResponse
                ]);
    
                DB::commit();
    
                return ResponseHelper::success('QR Generated Successfully', [
                    'txn_id'     => $payoutTxn->txn_id,
                    'amount'     => $response['Amount'],
                    'qr_string'  => $response['QRString'],
                    'status'     => $response['Status'],
                    'vendor_txn' => $response['TransId']
                ], 200);
            }
    
            // Failed case
            $payoutTxn->update([
                'status'       => 'FAILED',
                'api_response' => $apiResponse
            ]);
    
            DB::commit();
            return ResponseHelper::error(
                $response['Description'] ?? 'Transaction Failed',
                $response,
                400
            );
    
        } catch (\Exception $e) {
            DB::rollBack();
            return ResponseHelper::error('Transaction Update Failed', $e->getMessage(), 500);
        }
    }


}  



    

