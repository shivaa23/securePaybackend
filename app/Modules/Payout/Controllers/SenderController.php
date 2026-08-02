<?php

namespace App\Modules\Payout\Controllers;

use Laravel\Lumen\Routing\Controller as BaseController;
use App\Traits\ApiResponser;
use Illuminate\Http\Request;
use App\Modules\Payout\Models\PayoutSender;
use App\Modules\Payout\Models\PayoutBeneficiary;
use App\Modules\Payout\Models\SenderValidation;
use Auth;
use App\Modules\Message\Models\Sms;
use App\Modules\Template\Models\Template;
use App\Modules\Auth\Models\Otp;
use App\Helper\ResponseHelper;
use Illuminate\Support\Facades\Hash;
class SenderController extends BaseController
{
    use ApiResponser;

    public function __construct()
    {
        $this->middleware('auth', ['except' => ['updateSenderLimit']]);
        $this->middleware('check.active', ['except' => 'updateSenderLimit']);

    }
    
    public function updateSenderLimit(Request $request){
        if($request->ip() != '103.49.124.232'){
            return ResponseHelper::error('Unauthorised Action', '', 403);
        }
        PayoutSender::query()->update(['rem_limit' => 200000]);
        return ResponseHelper::success('Sender updated', '', 200);
    }
    public function registerSender(Request $request){
        $validator = SenderValidation::registerSender($request->all());
        if ($validator->fails()) {
            return $this->jsonResponse('1000', 'Failed', $validator->errors(), '', 422);
        }
    
        $user = Auth::user();
        if (!in_array($user->role, ["ret", "dd", "api"])) {
            return ResponseHelper::error('Unauthorised Action', '', 403);
        }
    
        $sender = new PayoutSender();
        $sender->user_id = $user->id;
        $sender->sender_name = $request->sender_name;
        $sender->email = $request->email;
        $sender->mobile_number = $request->mobile_number;
    
        if ($sender->save()) {
            $otp = Otp::loginOtp($user->id);
    		$template = Template::where('name','MOB_VERIFY')->first();
    		if (!$template) {
                return $this->jsonResponse('1001', 'Template not found', null, null, 500);
            }
    		$message = str_replace('{$otp}', $otp['otp'], $template->message);
    		$data = ["number"=>$sender->mobile_number,"message"=>$message, "tempid"=>$template->temp_id];
    		Sms::send($data,$user);
            return ResponseHelper::success('Sender Registered Successfully', [
                'sender'  => $sender,
                'otp_ref' => $otp['otp_ref']
            ], 200);
        }
        return ResponseHelper::error('Sender Registration Failed', '', 403);
    }
    public function verifySender(Request $request) {
        $validator = SenderValidation::verifySender($request->all());
        if ($validator->fails()) {
            return ResponseHelper::error('Validation failed', $validator->errors(), 422);
        }
    
        $user = Auth::user();
        if (!in_array($user->role, ["ret", "dd", "api"])) {
            return ResponseHelper::error('Unauthorised Action', '', 403);
        }
    
        $sender = PayoutSender::where("mobile_number", $request->mobile_number)->first();
        if (!$sender) {
            return ResponseHelper::error('Sender not found', '', 404);
        }
        $otpVerification = Otp::verifyOtp($request);
    
        if (!$otpVerification['success']) {
            return ResponseHelper::error($otpVerification['message'], $otpVerification['errors'] ?? null, 403);
        }
    
       
        $sender->is_active = 1;
        $sender->kyc_status = "verified";
        if ($sender->save()) {
            return ResponseHelper::success('Sender Verified Successfully', $sender, 200);
        }
    
        return ResponseHelper::error('Sender Verification Failed', '', 403);
    }
    // public function getSender(Request $request){
    //         $user = Auth::user();
        
    //         // 1️⃣ Check user role
    //         if (!in_array($user->role, ["ret", "dd", "api"])) {
    //             return ResponseHelper::error('Unauthorised Action', '', 403);
    //         }
        
    //         if (!$request->mobile_number) {
    //             return ResponseHelper::error('Mobile number required', '', 403);
    //         }
        
    //         $type = isset($request->type) ? strtoupper($request->type) : 'BANK';
    //         $sender = PayoutSender::with(['beneficiary' => function ($query) use ($type) {
    //             if ($type === 'UPI') {
    //                 $query->where('type', 'UPI');
    //             } else {
    //                 $query->where('type', 'BANK');
    //             }
    //         }])->where('mobile_number', $request->mobile_number)
    //           ->first();
    //         if (!$sender) {
    //             return ResponseHelper::success('Sender Not Found', '', 200);
    //         }
    //         if ($sender->is_active == 0) {
    //             $otp = Otp::loginOtp($user->id);
        
    //             $template = Template::where('name', 'MOB_VERIFY')->first();
    //             if (!$template) {
    //                 return ResponseHelper::error('Template not found',[],403);
    //             }
        
    //             $message = str_replace('{$otp}', $otp['otp'], $template->message);
    //             $data = [
    //                 "number" => $sender->mobile_number,
    //                 "message" => $message,
    //                 "tempid" => $template->temp_id
    //             ];
        
    //             Sms::send($data, $user);
        
    //             return ResponseHelper::error('The number is inactive', [
    //                 'mobile_number' => $sender->mobile_number,
    //                 'otp_ref' => $otp['otp_ref']
    //             ], 403);
    //         }
        
    //         if ($sender->kyc_status == 'pending') {
    //             return ResponseHelper::success('Sender Not Verified', '', 200);
    //         }
    //         return ResponseHelper::success('Payout transactions fetched', $sender, 200);
    //     }
    public function getSender(Request $request){
            $user = Auth::user();
        
            // 1️⃣ Check user role
            if (!in_array($user->role, ["ret", "dd", "api"])) {
                return ResponseHelper::error('Unauthorised Action', '', 403);
            }
        
            if (!$request->mobile_number) {
                return ResponseHelper::error('Mobile number required', '', 403);
            }
        
            $type = isset($request->type) ? strtoupper($request->type) : 'BANK';
            $sender = PayoutSender::with(['beneficiary' => function ($query) use ($type) {
                if ($type === 'UPI') {
                    $query->where('type', 'UPI');
                } else {
                     $query->where('type', '!=', 'UPI');
                }
            }])->where('mobile_number', $request->mobile_number)
              ->first();
            if (!$sender) {
                return ResponseHelper::success('Sender Not Found', '', 200);
            }
            if ($sender->is_active == 0) {
                $otp = Otp::loginOtp($user->id);
        
                $template = Template::where('name', 'MOB_VERIFY')->first();
                if (!$template) {
                    return ResponseHelper::error('Template not found',[],403);
                }
        
                $message = str_replace('{$otp}', $otp['otp'], $template->message);
                $data = [
                    "number" => $sender->mobile_number,
                    "message" => $message,
                    "tempid" => $template->temp_id
                ];
        
                Sms::send($data, $user);
        
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
    
    public function getSenderByAcc(Request $request){
            $user = Auth::user();
        
            if (!in_array($user->role, ["ret", "dd", "api"])) {
                return ResponseHelper::error('Unauthorised Action', '', 403);
            }
        
            if (!$request->account_number) {
                return ResponseHelper::error('Account number required', '', 403);
            }
        
            $type = isset($request->type) ? strtoupper($request->type) : 'BANK';
        
            $accountNumbers = PayoutBeneficiary::where('account_number', $request->account_number)
                ->where('type', $type)
                ->pluck('mobile_number')
                ->filter()
                ->values()
                ->toArray();
        
            if (count($accountNumbers) === 0) {
                return ResponseHelper::error('Account Not Found', '', 404);
            }
        
            return ResponseHelper::success('Account numbers fetched successfully', $accountNumbers, 200);
        }
    public function getSenders(){
        $user = Auth::user();
        if (!in_array($user->role, ["sadm", "adm"])) {
            return ResponseHelper::error('Unauthorised Action', '', 403);
        }
        $senders = PayoutSender::paginate(10);
        return ResponseHelper::success('payout Txns fetched', $senders, 200);
    }
    function verifySenderSchema(){
        $user = Auth::user();
   
        $senders = SenderValidation::verifySenderSchema();
        return ResponseHelper::success('Schema fetched', $senders, 200);
    }
    public function registerSenderSchema(){
        $user = Auth::user();
         $senders = SenderValidation::registerSenderSchema();
        return ResponseHelper::success('Schema fetched', $senders, 200);
    }
}
