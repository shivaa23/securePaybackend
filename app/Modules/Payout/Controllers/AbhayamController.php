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
class AbhayamController extends BaseController
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
    public function getSender4(Request $request)
    {
        $user = Auth::user();
    
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
            $q->where('type', 'ABHAYAM');
        }])->where('mobile_number', $mobile)
          ->where('pipe', 'ABHAYAM')->where('is_active', 1)
          ->first();
    
        if ($existingSender) {
            return ResponseHelper::success('Success', $existingSender, 200);
        }
    
        // 2) Not in DB -> call ABHAYAM Sender Check API
        $payload = [
            "Authcode"     => env("ABHAYAM_PAY_KEY"),
            "Mpin"         => env("ABHAYAM_PAY_MPIN"),
            "SenderMobile" => $mobile,
            "RequestID"    => (string) uniqid('req_'),
        ];
    
        $data = [
            'url'     => 'https://payoutsender.abhayampay.in/ProcessRequest/Check',
            'payload' => json_encode($payload),
            'method'  => 'POST'
        ];
    
        $response = Abhayam::service($data);
    
        // If ABHAYAM says not found or any failure, reply with Sender Not Found
        if (!isset($response['Status']) || $response['Status'] !== "Success") {
            return ResponseHelper::success('Sender Not Found', '', 200);
        }
    
        // 3) Save new sender (or update) after successful API response
        $senderName = $response['SenderName'] ?? '';
        $sender = PayoutSender::firstOrNew([
            'mobile_number' => $mobile,
            'pipe'          => 'ABHAYAM',
        ]);
    
        $sender->sender_name = $senderName;
        $sender->kyc_status  = 'verified';
         $sender->user_id  = $user->id;
        $sender->rem_limit   = $response['SenderBalance'] ?? null;
        $sender->is_active   = 1;
        $sender->save();
    
        // 4) Fetch beneficiaries from ABHAYAM API
        $benePayload = [
            "Authcode"     => env("ABHAYAM_PAY_KEY"),
            "Mpin"         => env("ABHAYAM_PAY_MPIN"),
            "SenderMobile" => $mobile,
            "RequestID"    => (string) uniqid('req_')
        ];
    
        $beneData = [
            "url"     => "https://payoutbeneficiary.abhayampay.in/ProcessRequest/GetDetails",
            "payload" => json_encode($benePayload),
            "method"  => "POST"
        ];
    
        $beneResponse = Abhayam::service($beneData);
    //   return $beneResponse;
        if (isset($beneResponse["Status"]) && $beneResponse["Status"] === "Success" && !empty($beneResponse["Data"])) {
            foreach ($beneResponse["Data"] as $bene) {
                // Use firstOrNew by unique keys and then update fields
                $beneficiary = PayoutBeneficiary::firstOrNew([
                    "sender_id"      => $sender->id,
                    "account_number" => $bene["AccountNo"],
                    "type"           => "ABHAYAM"
                ]);
    
                $beneficiary->beneficiary_name  = $bene["BeneficiaryName"] ?? null;
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
            $query->where('type', 'ABHAYAM');
        }])->where('id', $sender->id)->first();
    
        return ResponseHelper::success('Success', $senderWithBens, 200);
    }

    public function remitterLogin3(Request $request){
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
            "Authcode"      => env("ABHAYAM_PAY_KEY"),
            "Mpin"          => env("ABHAYAM_PAY_MPIN"),
            "SenderMobile"  => $request->mobile_number,
            "SenderName"    => $f_name,
            "RequestID"     => (string) uniqid('req_'),
        ];
    
        $data = [
            'url'     => 'https://payoutsender.abhayampay.in/ProcessRequest/Register',
            'payload' => json_encode($payload),
            'method'  => 'POST'
        ];
    
        $response = Abhayam::service($data);
    
        if (!is_array($response) || !isset($response['Status'])) {
            return ResponseHelper::error('Something went wrong', [], 400);
        }
    
        switch ($response['Status']) {
    
            case "Success":
    
                // 🔍 Check if sender already exists
                $sender = PayoutSender::where('mobile_number', $request->mobile_number)
                            ->where('pipe', 'ABHAYAM')
                            ->first();
    
                if ($sender) {
                    // ✅ Update existing sender
                    $sender->sender_name = $response['SenderName'] ?? $f_name;
                    // $sender->aadhar      = $request->aadhar;
                    // $sender->pan         = $request->pan;
                    $sender->kyc_status  = "pending";
                    $sender->is_active   = 0;
                    $sender->user_id     = $user->id;
                    $sender->save();
                } else {
                    // ✅ Create new sender
                    $sender = new PayoutSender();
                    $sender->sender_name   = $response['SenderName'] ?? $f_name;
                    $sender->mobile_number = $request->mobile_number;
                    $sender->pipe          = "ABHAYAM";
                    // $sender->aadhar        = $request->aadhar;
                    // $sender->pan           = $request->pan;
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

    public function verifyRemitterLogin3(Request $request){
        $validator = SenderValidation::verifySender($request->all());
        if ($validator->fails()) {
            return ResponseHelper::error('Validation failed', $validator->errors(), 422);
        }
    
        $user = Auth::user();
     
        if (!in_array($user->role, ['ret', 'dd', 'api'])) {
            return ResponseHelper::error('Unauthorized Action', null, 403);
        }
        $sender = PayoutSender::where('mobile_number', $request->mobile_number)
                    ->where('pipe', "ABHAYAM")
                    ->first();
    
        if (!$sender) {
            return ResponseHelper::error('Sender not found', null, 404);
        }
        // Build Verify Sender Payload
        $payload = [
            "Authcode"     => env("ABHAYAM_PAY_KEY"),
            "Mpin"         => env("ABHAYAM_PAY_MPIN"),
            "ReferenceId"  => $request->otp_ref,       // required from registration
            "OTP"          => $request->otp,
            "RequestID"    => (string) uniqid('req_'),
        ];
    
        $data = [
            'url'     => 'https://payoutsender.abhayampay.in/ProcessRequest/Verify',
            'payload' => json_encode($payload),
            'method'  => 'POST'
        ];
    
    
        $response = Abhayam::service($data);
        // return $response;
        if (!is_array($response) || !isset($response['Status'])) {
            return ResponseHelper::error('Invalid API response', $response, 400);
        }
    
        // Process Response
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
    
        // Failed Verification
        return ResponseHelper::error(
            $response['Description'] ?? 'Verification Failed',
            $response,
            400
        );
    }
    public function getAbhayamBanks(Request $request){
        $user = Auth::user();
        if (!in_array($user->role, ["ret", "dd", "api"])) {
            return ResponseHelper::error('Unauthorized Action', '', 403);
        }
        $payload = [
            "Authcode"  => env("ABHAYAM_PAY_KEY"),
            "Mpin"      => env("ABHAYAM_PAY_MPIN"),
            "RequestID" => (string) uniqid('req_'),
        ];
    
        $data = [
            'url'     => 'https://payoutbeneficiary.abhayampay.in/ProcessRequest/GetBankDetails',
            'payload' => json_encode($payload),
            'method'  => 'POST'
        ];
    
        // Call Abhayam API
        $response = Abhayam::service($data);
    
        // Validate API response
        if (!isset($response['Status'])) {
            return ResponseHelper::error('Invalid response from Abhayam API', [], 500);
        }
    
        if ($response['Status'] !== "Success") {
            return ResponseHelper::error($response['Description'] ?? 'Failed', [], 400);
        }
    
        // Extract bank list
        $bankList = $response['Data'] ?? [];
    
        return ResponseHelper::success("Bank List Fetched Successfully", $bankList, 200);
    }
    public function addBeneficiaryAbhayam(Request $request){
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
            ->where('pipe', "ABHAYAM")->first();
    
        if (!$sender) {
            return ResponseHelper::error('Sender not found', [], 409);
        }
        $bene = preg_replace('/[^A-Za-z0-9 ]/', '', $request->bene_name);
        $bene = preg_replace('/\s+/', ' ', $bene);
        $bene = trim($bene);
        $payload = [
            "Authcode"        => env("ABHAYAM_PAY_KEY"),
            "Mpin"            => env("ABHAYAM_PAY_MPIN"),
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
            'url'     => 'https://payoutbeneficiary.abhayampay.in/ProcessRequest/Add',
            'payload' => json_encode($payload),
            'method'  => 'POST'
        ];
    
        $response = Abhayam::service($data);
        // return $response;
        if (!isset($response['Status'])) {
            return ResponseHelper::error('Invalid response from Abhayam API', [], 500);
        }
    
        if ($response['Status'] !== "Success") {
            return ResponseHelper::error($response['Description'] ?? 'Failed', [], 400);
        }
        $existing = PayoutBeneficiary::where('mobile_number', $request->mobile_number)
            ->where('account_number', $request->bene_account)
            ->where('ifsc_code', $request->ifsc_code)
            ->where('type', "ABHAYAM")
            ->first();
    
        if ($existing) {
            return ResponseHelper::error('Beneficiary already exists', [], 409);
        }
        $beneficiary = new PayoutBeneficiary();
        $beneficiary->beneficiary_name = $bene;
        $beneficiary->ifsc_code        = strtoupper($request->ifsc_code);
        $beneficiary->account_number   = $request->bene_account;
        $beneficiary->bank_name        = $request->bank_name;
        $beneficiary->type             = 'ABHAYAM';
        $beneficiary->api_id       = $response['BenficiaryId'] ?? null;
        $beneficiary->sender_id        = $sender->id;
        $beneficiary->mobile_number    = $request->mobile_number;
        $beneficiary->is_verified      = $request->is_verified;
        $beneficiary->save();
    
        return ResponseHelper::success('Beneficiary Added Successfully', $beneficiary, 200);
    }
    public function deleteBeneficiaryAbhayam(Request $request){
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
            "Authcode"      => env("ABHAYAM_PAY_KEY"),
            "Mpin"          => env("ABHAYAM_PAY_MPIN"),
            "SenderMobile"  => (string) $request->mobile_number,
            "RequestID"     => uniqid('req_'),
            "BeneficiaryId" => (string) $request->bene_id,
        ];
        $data = [
            'url'     => 'https://payoutbeneficiary.abhayampay.in/ProcessRequest/Delete',
            'payload' => json_encode($payload),
            'method'  => 'POST'
        ];
        $response = Abhayam::service($data);
        if (empty($response) || !isset($response['Status'])) {
            return ResponseHelper::error('Invalid response from Abhayam API', [], 500);
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
    public function payOutAbhayam(Request $request){ 
            $user = Auth::user();
        
            // Validation
           $validator = SoliteckValidation::payOutAbhayam($request->all());
        if ($validator->fails()) {
            return ResponseHelper::error('Validation failed', $validator->errors(), 422);
        }
            // Duplicate check
            $dupCheck = Uuid::check($request->client_ref, 'payOut2', $request);
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
                ->where('pipe', "ABHAYAM")
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
                "Authcode"      => env("ABHAYAM_PAY_KEY"),
                "Mpin"          => env("ABHAYAM_PAY_MPIN"),
                "RequestID"     => (string)$payoutTxn->txn_id,
                "ServiceId"     =>"27",
                "SenderMobile"  => (string)$request->mobile_number,
                "BeneficiaryId" => (string)$request->bene_id,
                "Amount"        => (string)$request->amount,
                "AdharNo"       => "123123123123",
                "PanNo"         => (string)$user->pan,
                "Lat"           => (string)$request->latitude??22.753,
                "Long"          => (string)$request->longitude??78.773
            ];
        
            $data = [
                'url'     => 'https://payoutransaction.abhayampay.in/ProcessRequest/transaction',
                'payload' => json_encode($payload),
                'method'  => 'POST'
            ];
        //  return $data;
            $response = Abhayam::service($data);
            // return $response;
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
    
}  
    

