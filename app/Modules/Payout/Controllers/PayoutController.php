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
use Auth;
use DB;
use Carbon\Carbon;
use App\Helper\ResponseHelper;
use App\Modules\Auth\Models\Otp;
use App\Modules\User\Models\User;
use App\Modules\Misc\Models\Uuid;
use App\Modules\Payout\Services\PayoutService;
use App\Modules\WalletLedger\Services\WalletService;
use App\Modules\AuditLog\Services\AuditService;
use App\Modules\Payout\Services\PayoutResponseHandler;
use App\Modules\Message\Models\Sms;
use App\Modules\Template\Models\Template;
use App\Modules\Payout\Models\PayoutBeneficiary;
use App\Modules\Integration\Models\Utkal;
use App\Modules\Integration\Models\Abhayam;
class PayoutController extends BaseController
{
    use ApiResponser;

   public function __construct(){
        $this->middleware('auth', ['except' => ['']]);
        $this->middleware('check.active', ['except' => '']);
         $this->middleware('check.whitelist', ['except' => 'getPayoutTxns','getPayoutSchema','upiVerifiaction','createPurpose','getPurpose']);

    }
   public function getPayoutTxns(Request $request){
        $user = Auth::user();
        $query = PayoutTxn::with(['user:id,establishment']);
         $status=$request->status=="refund"?"refunded":$request->status;
        switch ($user->role) {
            case 'sadm':
            case 'adm':
                if($request->boolean('export')) {
                   $query->select([
                        'id', 'txn_id', 'user_id', 'mobile_number', 'beneficiary_name', 'account_number', 'ifsc_code','bank_name','route',
                        'operator','operator_id','amount','ret_comm','ret_tds','di_comm','di_tds','md_comm','md_tds','a_comm','purpose',
                        'charges','gst','status','mop','created_at','updated_at'
                    ]); 
                }
                break;
            case 'md':
                $userIds = User::getDescendantUserIds($user->id);
                $userIds = array_merge([$user->id], $userIds ?? []);
                $query->select([
                    'id', 'txn_id', 'user_id', 'pf', 'mobile_number', 'beneficiary_name', 'account_number', 'ifsc_code','bank_name',
                    'operator','operator_id','amount','ret_comm','ret_tds','di_comm','di_tds','md_comm','md_tds','purpose','charges','gst','status','mop','created_at','updated_at'
                ])->whereIn('user_id', $userIds);
                break;
            case 'di':
                $userIds = User::getDescendantUserIds($user->id);
                $userIds = array_merge([$user->id], $userIds ?? []);
                $query->select([
                    'id', 'txn_id', 'user_id', 'pf', 'mobile_number', 'beneficiary_name', 'account_number', 'ifsc_code','bank_name',
                    'operator','operator_id','amount','ret_comm','ret_tds','di_comm','di_tds','purpose','charges','gst','status','mop','created_at','updated_at'
                ])->whereIn('user_id', $userIds);
                break;
            case 'dd':
            case 'api':
            case 'ret':
                $query->select([
                'id', 'txn_id', 'user_id', 'pf', 'mobile_number', 'beneficiary_name', 'account_number', 'ifsc_code','bank_name',
                'operator','operator_id','amount','ret_comm','ret_tds','purpose','charges','gst','status','mop','created_at','updated_at'
            ])->where('user_id', $user->id);
                break;
            default:
            return ResponseHelper::error('Unauthorized role access', [], 403);
        }
        $query
            ->when($request->filled('status'), fn($q) => 
                $q->where('status', strtoupper($status))
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
             ->when($request->filled('beneficiary_name'), fn($q) => 
                $q->where('beneficiary_name', $request->beneficiary_name)
            )
            ->when($request->filled('txn_id'), fn($q) => 
                $q->where('txn_id', $request->txn_id)
            )
              ->when($request->filled('service'), fn($q) => 
                $q->where('operator', $request->service)
            )
            ->when($request->filled('from_date') && $request->filled('to_date'), fn($q) => 
                $q->whereBetween('created_at', [
                    $request->from_date . ' 00:00:00',
                    $request->to_date . ' 23:59:59'
                ])
            );
        if ($request->boolean('export')) {
              ini_set('memory_limit', '2048M');

    $txns = [];

    foreach ($query->latest()->cursor() as $txn) {
        $txn->establishment = $txn->user->establishment ?? 'Unknown';
        unset($txn->user);
        $txns[] = $txn;
    }

    return ResponseHelper::success('Transactions fetched', $txns, 200);
}

        $txns = $query->latest()->paginate($request->input('paginate', 15));
        $txns->getCollection()->transform(function ($txn) {
            $txn->establishment = $txn->user->establishment ?? 'Unknown';
            unset($txn->user);
            return $txn;
        });
        return ResponseHelper::success('Transactions fetched', $txns, 200);
    }
   public function getPayoutSchema(){
        $schema=PayoutValidation::getPayoutSchema();
        return ResponseHelper::success('Schema fetched', $schema, 200);
    }
   public function payOut(Request $request){
        $user= Auth::user();
        $dupCheck = Uuid::check($request->client_ref,'payOut1',$request);
        if($dupCheck['code'] != '200'){
            return ResponseHelper::error('Duplicate Transaction',[],402);
        }
        $validator = PayoutValidation::payOut($request->all());
        if ($validator->fails()) {
            return ResponseHelper::error('Validation failed',$validator->errors(),422);
        }
        $timeWindow = Carbon::now()->subMinutes(2);
        $duplicate = PayoutTxn::where('account_number', $request->account_number)
            ->where('amount', $request->amount)
            ->whereIn('status', ['PENDING', 'SUCCESS'])
            ->where('created_at', '>=', $timeWindow)
            ->exists();
        if ($duplicate) {
            return ResponseHelper::error('Transaction With Same Amount To This Account No Is Under Process Try After 2 Minutes Or Try Changing Amount',[],429);
        }
        $mpin = OTP::verifyMpin($request->mpin);
        if(!$mpin){
             return ResponseHelper::error("invalid mpin", [], 403);
        }
        if (!in_array($user->role, ["api", "dd", "ret"])) {
            return ResponseHelper::error('Action not Authorised', '', 403);
        }
        $sender = PayoutSender::where('mobile_number',$request->mobile_number)->where('is_active',1)->first();
        if(!$sender){
            return ResponseHelper::error('Sender Not Found Or Unverified', '', 403);
        }
        if($sender->rem_limit < $request->amount){
            return ResponseHelper::error('Sender Limit Exceeds', '', 403);
        }
        $service = Service::find($request->operator);
           if(!$service){
            return ResponseHelper::error('Service not Configured', '', 403);
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
        
        if ($user->w1 < ($request->amount+$user->lien) * 100 + ($comm['ret']?? $comm['dd']) *100) {
            return ResponseHelper::error('Insufficient W1 Balance', '', 403);
        }
    
        DB::beginTransaction();
        try {
            try {
                $payoutTxn = PayoutService::create($user, $service, $request, $comm, $a_comm);
            } catch (\Illuminate\Database\QueryException $e) {
                if ($e->errorInfo[1] == 1062) { // duplicate entry
                    return ResponseHelper::error('Duplicate client_ref detected', [], 409);
                }
                throw $e;
            }
       
            // Deduct wallet
            WalletService::debit($user, $request->amount, $payoutTxn, $service);
    
            // Create audit log
            $auditLog = AuditService::logRequest($user, $request, $service);
    
            DB::commit();
        } catch (\Exception $e) {
            DB::rollBack();
            return ResponseHelper::error('Transaction Init Failed', $e->getMessage(), 500);
        }
    
        // Step 2: Call external API
        // $response = Impsguru::payout($payoutTxn, $service);
           $response = $this->router($payoutTxn, $service,$request,$sender);
            // return $response;
        DB::beginTransaction();
        try {
         
            //return $result;
             $route = strtoupper($service->route ?? '');
    
        switch ($route) {
    
            case 'IMGURU':
            case 'IMPSGURU':
               $result = PayoutResponseHandler::handle($payoutTxn, $response, $user, $service, $comm, $sender);
                  break;
            case 'ABHAYAM':
                $result = PayoutResponseHandler::handleResponseAbhyam($payoutTxn, $response, $user, $service, $comm, $sender);
                   break;
            case 'UTKAL':
                 $result = PayoutResponseHandler::handleResponseAbhyam($payoutTxn, $response, $user, $service, $comm, $sender);
                    break;
            default:
                return ResponseHelper::error('Invalid Route in helper', '', 404);
        }
            AuditService::updateResponse($auditLog, $response);
    
            DB::commit();
            return $result;
        } catch (\Exception $e) {
            DB::rollBack();
            return ResponseHelper::error('Transaction Update Failed', $e->getMessage(), 500);
        }
    }
   public function router($payoutTxn, $service, $request,$sender){
        $route = strtoupper($service->route ?? '');
        switch ($route) {
    
            case 'IMGURU':
            case 'IMPSGURU':
                return Impsguru::payout($payoutTxn, $service,$request);
            case 'ABHAYAM':
                return self::abhayamPayout($payoutTxn, $service, $request,$sender);
            case 'UTKAL':
                return self::utklPayout($payoutTxn, $service, $request,$sender);
            default:
                return ResponseHelper::error('Invalid Route in router', '', 404);
        }
    }
   public static function utklPayout($payoutTxn, $service, $request,$sender){
          $user=Auth::user();
      $payload = [
                "Authcode"      => env("UTKAL_PAY_KEY"),
                "Mpin"          => env("UTKAL_PAY_MPIN"),
                "RequestID"     => (string)$payoutTxn->txn_id,
                "ServiceId"     => 27,
                "SenderMobile"         => (string) $request->mobile_number, 
                "SenderName"           => (string) $sender->sender_name,
                "BankName"             => (string) $request->bank_name,
                "BankCode"             => (string) ($request->bank_code ?? "MAGNI"), 
                "BankAccountNumber"    => (string) $request->account_number, 
                "BeneficiaryName"      => (string) $request->beneficiary_name, 
                "BankIfsc"             => (string) $request->ifsc_code,
            
                "Amount"               => (string) $request->amount,
                "AdharNo"              => (string) ($request->adhar_no ?? "123123123123"), 
                "PanNo"                => (string) $user->pan,
            
                "Lat"                  => (string) ($request->latitude ?? "22.753"),
                "Long"                 => (string) ($request->longitude ?? "78.773") 
            ];
        $data = [
            'url'     => 'https://singleptxn.utkaldigital.co.in/ProcessRequest/transaction',
            'payload' => json_encode($payload),
            'method'  => 'POST'
        ];
    
        return Utkal::service($data);
    }
   public static function abhayamPayout($payoutTxn, $service, $request,$sender){
        $user=Auth::user();
           $payload = [
                "Authcode"             => env("ABHAYAM_PAY_KEY"),  
                "Mpin"                 => env("ABHAYAM_PAY_MPIN"),
                "RequestID"            => (string) $payoutTxn->txn_id,
                "ServiceId"            => "27",
            
                "SenderMobile"         => (string) $request->mobile_number,
                "SenderName"           => (string) $sender->sender_name,
            
                "BankName"             => (string) $request->bank_name,
                "BankCode"             => (string) ($request->bank_code ?? "SAND"),
                "BankAccountNumber"    => (string) $request->account_number,
                "BeneficiaryName"      => (string) $request->beneficiary_name,
                "BankIfsc"             => (string) $request->ifsc_code,
            
                "Amount"               => (string) $request->amount,
                "AdharNo"              => (string) ($request->adhar_no ?? "123123123123"),
                "PanNo"                => (string) $user->pan,
            
                "Lat"                  => (string) ($request->latitude ?? "22.753"),
                "Long"                 => (string) ($request->longitude ?? "78.773")
            ];
           $data = [
                'url'     => 'https://singleptxn.abhayampay.in/ProcessRequest/transaction',
                'payload' => json_encode($payload),
                'method'  => 'POST'
            ];
        return Abhayam::service($data);
    }
   public function upiVerifiaction(Request $request){
        return ResponseHelper::error('This Service Not Active', '', 403);
        $user = Auth::user();
        $validator = PayoutValidation::upiVerify($request->all());
        if ($validator->fails()) {
            return $this->jsonResponse('1000', 'Failed', $validator->errors(), '', 422);
        }
        $mpin = OTP::verifyMpin($request->mpin);
        if(!$mpin){
             return ResponseHelper::error("invalid mpin", [], 403);
        }
        if (!in_array($user->role, ["api", "dd", "ret"])) {
            return ResponseHelper::error('Action not Authorised', '', 403);
        }
       
        $ben= PayoutBeneficiary::where("account_number",$request->account_number)->where("mobile_number",$request->mobile_number)->first();
        if (!$ben) {
            return ResponseHelper::error('Beneficiary Not Found ', '', 403);
        }
        $service = Service::find($request->operator);
        if (!$service) {
            return ResponseHelper::error('Invalid Service/Operator', '', 403);
        }
        $amount = 1;
        $baseCommission = $comm['ret'] ?? $comm['dd'] ?? 0;
        $totalAmount = $amount + $baseCommission + round($baseCommission * 0.18, 2);
        
        $comm = CommRule::calculateCommission($user, $service->name, $amount);

        if (in_array($user->role, ["api", "dd", "ret"])) {
            $comm = CommRule::finalCommission($comm, $amount,$service);
        }
    
        if ($user->w1 < ($request->amount * 100) + $totalAmount) {
            return ResponseHelper::error('Insufficient W1 Balance', '', 403);
        }
        DB::beginTransaction();
        try {
            $payoutTxn = PayoutService::upiVerify($user, $service, $request, $comm,$totalAmount);
            WalletService::debit($user, $totalAmount, $payoutTxn, $service);
            $auditLog = AuditService::logRequest($user, $request, $service);
            DB::commit();
        } catch (\Exception $e) {
            DB::rollBack();
            return ResponseHelper::error('Transaction Init Failed', $e->getMessage(), 500);
        }
        
        $response = Impsguru::vpaVerification($payoutTxn, $service);
        
        DB::beginTransaction();
        try {
            $result = PayoutResponseHandler::handleVerification($payoutTxn, $response, $user, $service, $comm, $ben);
            AuditService::updateResponse($auditLog, $response);
            DB::commit();
            return $result;
        } catch (\Exception $e) {
            DB::rollBack();
            return ResponseHelper::error('Transaction Update Failed', $e->getMessage(), 500);
        }
    }
   public function createPurpose(Request $request){
        $user=Auth::user();
        try {
           
            $validator = PayoutValidation::createPurpose($request->all());
            if ($validator->fails()) {
                return ResponseHelper::error(
                    'Validation failed',
                    $validator->errors(),
                    422
                );
            }
       if (!in_array($user->role, ["sadm", "adm"])) {
            return ResponseHelper::error('unauthorized', [],403);
        }

            $purpose = new Purpose();
            $purpose->type = $request->type;
            $purpose->description = $request->description ?? null;
            $purpose->save();
    
            return ResponseHelper::success(
                'Purpose created successfully',
                $purpose,
                201
            );
    
        } catch (\Exception $e) {
            return ResponseHelper::error(
                'Something went wrong',
                $e->getMessage(),
                500
            );
        }
    }
   public function getPurpose(Request $request){
     try {
         $purpose =Purpose::all();
          return ResponseHelper::success('Purpose fetch successfully',$purpose,200);
         }catch (\Exception $e) {
            return ResponseHelper::error('Something went wrong', $e->getMessage(),500);
        }
    }
   public function get_payout_senderName(Request $request){
        try {
            $user = auth()->user();
            
            if (!$user) {
                return ResponseHelper::error('Unauthorized access', [], 401);
            }
    
            if (!in_array($user->role, ["sadm", "adm"])) {
                return ResponseHelper::error('Unauthorized', [], 403);
            }
            
            // Build query with filters
            $query = PayoutSender::query();
            
            // Filter by exact sender_name match
            if ($request->filled('sender_name')) {
                $query->where('sender_name', $request->sender_name);
            }
            
            // Filter by exact mobile_number match
            if ($request->filled('mobile_number')) {
                $query->where('mobile_number', $request->mobile_number);
            }
            
            // Filter by pipe
            if ($request->filled('pipe')) {
                $query->where('pipe', $request->pipe);
            }
            
            // Export vs Pagination logic
            if ($request->filled('export') && $request->export == 1) {
                $payoutSenders = $query->get();
            } else {
                $perPage = $request->filled('paginate') ? $request->paginate : 15;
                $payoutSenders = $query->paginate($perPage);
            }
            
            return ResponseHelper::success(
                'Payout senders fetched successfully',
                $payoutSenders,
                200
            );
    
        } catch (\Exception $e) {
            return ResponseHelper::error(
                'Something went wrong',
                $e->getMessage(),
                500
            );
        }
    }
   public function update_payout_sender(Request $request){
        $validator = PayoutValidation::updatePayoutSender($request->all(), [
            'sender_name' => 'required|string|max:255',
            'mobile_number' => 'sometimes|numeric|digits:10',
            'rem_limit' => 'sometimes|numeric|min:0',
        ]);
    
        if ($validator->fails()) {
            return ResponseHelper::error($validator->errors(), 'Validation Error', 422);
        }
    
        if (!in_array(Auth::user()->role, ['sadm', 'adm'])) {
            return ResponseHelper::error('Unauthorised Action', [], 403);
        }
    
        $payoutSender = PayoutSender::find($request->id);
        
        if (!$payoutSender) {
            return ResponseHelper::error('Payout sender not found', '', 403);
        }
    
        if($payoutSender){
            $payoutSender->sender_name = $request->sender_name ?? $payoutSender->sender_name;
            $payoutSender->mobile_number = $request->mobile_number ?? $payoutSender->mobile_number;
            $payoutSender->rem_limit = $request->rem_limit ?? $payoutSender->rem_limit;
            
            if($payoutSender->save()){
                return ResponseHelper::success('Payout sender updated Successfully', $payoutSender, 200);
            }
        } else {
            return ResponseHelper::error('Payout sender not found', '', 403);
        }
    }
   public function delete_payout_sender(Request $request){
        $validator = PayoutValidation::updatePayoutSender($request->all());
        
        if ($validator->fails()) {
            return ResponseHelper::error('Validation Failed', $validator->errors(), 422);
        }
    
        if (!in_array(Auth::user()->role, ['sadm', 'adm'])) {
            return ResponseHelper::error('Unauthorised Action', [], 403);
        }
    
        $payoutSender = PayoutSender::find($request->id);
        
        if (!$payoutSender) {
            return ResponseHelper::error('Payout sender not found', '', 403);
        }
    
        if($payoutSender->delete()){
            return ResponseHelper::success('Payout sender deleted Successfully', [], 200);
        } else {
            return ResponseHelper::error('Failed to delete payout sender', '', 500);
        }
    }

}
