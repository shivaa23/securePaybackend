<?php

namespace App\Modules\Auth\Controllers;

use Laravel\Lumen\Routing\Controller as BaseController;
use App\Traits\ApiResponser;
use Illuminate\Http\Request;
use Auth;
use App\Modules\User\Models\User;
use App\Modules\Auth\Models\AuthValidation;
use App\Modules\Auth\Models\QrSession;
use App\Modules\AuditLog\Models\AuditLog;
use App\Modules\UserDevice\Models\UserDevice;
use App\Modules\Template\Models\Template;
use App\Modules\Auth\Models\Otp;
use App\Modules\Message\Models\Sms;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Crypt;
use App\Modules\WalletLedger\Models\WalletLedger;
use Carbon\Carbon;
use Validator;
use App\Helper\ResponseHelper;
class AuthController extends BaseController
{
    use ApiResponser;
    public function __construct() {
        $this->middleware('auth', ['except' => ['signIn','secureLogin','forgetPassword','resetMpin','getMobile',"resendOtp","generateQr","checkQr"]]);
        $this->middleware('check.active', ['except' => ['signIn','secureLogin','forgetPassword','resetMpin','getMobile',"resendOtp","generateQr","checkQr"]]);
        $this->middleware('check.service.time',['except' => '']);
    }
    public function generateQr() {
        $token = Str::uuid()->toString();
        $qr = QrSession::create([
            'token' => $token,
            'expires_at' => Carbon::now()->addMinutes(2),
        ]);
        return $this->jsonResponse('1000', 'Success', 'QR Created',$token, 200);
    }
    public function checkQr(Request $request) {
        $qr = QrSession::where('token', $request->token)
            ->where('expires_at', '>', Carbon::now())
            ->first();

        if (!$qr) {
            return $this->jsonResponse('1001', 'Success', 'Token Expired','', 410);
        }

        if ($qr->status === 'approved') {
            $user = User::find($qr->user_id);
            return $this->jsonResponse('1000', 'approved', 'Token Approved',$user->api_token, 200);
        }

        return response()->json(['status' => 'pending']);
    }
    public function approveQr(Request $request) {
        $validator = AuthValidation::approveQr($request->all());
        if ($validator->fails()) {
            return $this->jsonResponse('1000', 'Failed', $validator->errors(),'', 422);
        }

        $qr = QrSession::where('token', $request->token)
            ->where('expires_at', '>', Carbon::now())
            ->where('status', 'pending')
            ->first();

        if (!$qr) {
            return $this->jsonResponse('1001', 'Failed', 'Invalid or expired token','', 400);
        }

        $user = Auth::user();
        if($user->role != 'api'){
            $token = Str::random(60);
            $user->api_token = $token;
            $user->encryption_key = Crypt::encrypt(Str::random(16));
            $user->save();   
        }else{
            $token = $user->api_token;
        }
        $this->storeLoginDevice($request, $user);
        AuditLog::create([
            'user_id'      => $user->id,
            'role'         => $user->role,
            'action'       => 'SECURE_LOGIN',
            'service_name' => 'auth',
            'request_data' => json_encode($request->token),
            'response_data'=> json_encode(['message' => 'QR Logged In']),
            'ip_address'   => $request->ip(),
            'user_agent'   => $request->header('User-Agent'),
            'status'       => 'Success'
        ]);
        $qr->update([
            'status' => 'approved',
            'user_id' => $user->id,
        ]);
        return $this->jsonResponse('1000', 'approved', 'Web Login Success','', 200);
    }
    public function signIn(Request $request){
		$validator = AuthValidation::signIn($request->all());
        if ($validator->fails()) {
            return $this->jsonResponse('1000', 'Failed', $validator->errors(),'', 422);
        }
        $agent = $request->header('User-Agent');
        // return $agent;
        $allowedAgents = ['Transup/1.0','ImpsguruAppWeb/1.0','Mozilla'];
        $allowed = false;
        foreach ($allowedAgents as $allowedAgent) {
            if (str_contains($agent, $allowedAgent)) {
                $allowed = true;
                break;
            }
        }
        if (!$allowed) {
            return $this->jsonResponse('1000', 'Failed', 'Browser Direct Login Not Allowed To Login','', 422);
        }
        $username=$request->username;
         if (!str_starts_with($username, 'TRANS')) {
        return $this->jsonResponse('1002','Failed','Invalid username','',422 );
         }
	    $user = User::find(substr($request->username, 5));
		if($user){
            $pass = $request->password.env('PASS_KEY');
            if (Hash::check($pass, $user->password)) {
                AuditLog::create([
                    'user_id'      => $user->id,
                    'role'         => $user->role,
                    'action'       => 'LOGIN',
                    'service_name' => 'auth',
                    'request_data' => json_encode($request->username),
                    'response_data'=> json_encode(['message' => 'Logged In']),
                    'ip_address'   => $request->ip(),
                    'user_agent'   => $request->header('User-Agent'),
                    'status'       => 'Success'
                ]);
                return $this->login($request, $user);
            }else{
                AuditLog::create([
                    'user_id'      => $user->id,
                    'role'         => $user->role,
                    'action'       => 'LOGIN',
                    'service_name' => 'auth',
                    'request_data' => json_encode($request->username),
                    'response_data'=> json_encode(['message' => 'Unauthorized']),
                    'ip_address'   => $request->ip(),
                    'user_agent'   => $request->header('User-Agent'),
                    'status'       => 'Failed'
                ]);
               return $this->jsonResponse('1001', 'Failed', 'Wrong Password', '', 403); 
            }
		}else{
		    AuditLog::create([
                    'user_id'      => null,
                    'role'         => 'guest',
                    'action'       => 'LOGIN',
                    'service_name' => 'auth',
                    'request_data' => json_encode($request->username),
                    'response_data'=> json_encode(['message' => 'User Not Registered']),
                    'ip_address'   => $request->ip(),
                    'user_agent'   => $request->header('User-Agent'),
                    'status'       => 'Failed'
                ]);
			return $this->jsonResponse('1001', 'Failed', 'User Not Registered With This User ID', '', 404);
		}
    }
    public function login($request, $user){
        $agent = $request->header('User-Agent');
        $allowedAgents = ['Transup/1.0','ImpsguruAppWeb/1.0','Mozilla'];
        $allowed = false;
        foreach ($allowedAgents as $allowedAgent) {
            if (str_contains($agent, $allowedAgent)) {
                $allowed = true;
                break;
            }
        }
        if (!$allowed) {
            return $this->jsonResponse('1000', 'Failed', 'Browser Direct Login Not Allowed To Login','', 422);
        }
        if($allowed == 'Transup/1.0'){
            if($user->imei != $request->header('imei-number')){
                $otp = Otp::loginOtp($user->id);
    			$template = Template::where('name','MOB_VERIFY')->first();
    			if (!$template) {
                    return $this->jsonResponse('1001', 'Template not found', null, null, 500);
                }
    	     	$message = str_replace('{$otp}', $otp['otp'], $template->message);
    			$data = ["number"=>$user->mobile,"message"=>$message, "tempid"=>$template->temp_id];
    			Sms::send($data,$user);
    			return $this->jsonResponse('1000', 'Success', $otp['otp_ref'], 'OTP', 200);
            }
        }
		if($user->two_fa == 'OTP'){
			$otp = Otp::loginOtp($user->id);
			$template = Template::where('name','MOB_VERIFY')->first();
			if (!$template) {
                return $this->jsonResponse('1001', 'Template not found', null, null, 500);
            }
	     	 $message = str_replace('{$otp}', $otp['otp'], $template->message);
			$data = ["number"=>$user->mobile,"message"=>$message, "tempid"=>$template->temp_id];
			Sms::send($data,$user);
			return $this->jsonResponse('1000', 'Success', $otp['otp_ref'], 'OTP', 200);
		}elseif($user->two_fa == 'MPIN'){
			$device = UserDevice::where('ip',$request->ip())->where('device', $request->header('User-Agent'))->first();
			if($device){
				return $this->jsonResponse('1000', 'Success', 'Ask For MPIN', 'MPIN', 200);
			}else{
				$otp = Otp::loginOtp($user->id);
    			$template = Template::where('name','MOB_VERIFY')->first();
                 $message = str_replace('{$otp}', $otp['otp'], $template->message);
			    $data = ["number"=>$user->mobile,"message"=>$message, "tempid"=>$template->temp_id];
    			$res = Sms::send($data,$user);
    			return $this->jsonResponse('1000', $res, $otp['otp_ref'], 'OTP', 200);
			}
		}else{
			$device = UserDevice::where('ip',$request->ip())->where('device', $request->header('User-Agent'))->first();
			if($device){
				$otp = Otp::loginOtp($user->id);
    			$template = Template::where('name','MOB_VERIFY')->first();
    			$message = str_replace('{$otp}', $otp['otp'], $template->message);
			    $data = ["number"=>$user->mobile,"message"=>$message, "tempid"=>$template->temp_id];
    			Sms::send($data,$user);
    			return $this->jsonResponse('1000', 'Success', $otp['otp_ref'], 'OTP', 200);
			}else{
				$otp = Otp::loginOtp($user->id);
    			$template = Template::where('name','MOB_VERIFY')->first();
    			$message = str_replace('{$otp}', $otp['otp'], $template->message);
			    $data = ["number"=>$user->mobile,"message"=>$message, "tempid"=>$template->temp_id];
    			Sms::send($data,$user);
    			return $this->jsonResponse('1000', 'Success', $otp['otp_ref'], 'OTP', 200);
			}
		}
	}
	public function handle(){
        $users = User::all();

        foreach ($users as $user) {
            $ledger = WalletLedger::where('user_id', $user->id)
                        ->orderBy('id', 'desc')
                        ->first();

            if ($ledger) {
                if ((float)$user->w1 !== (float)$ledger->closing_balance) {
                    AuditLog::create([
                        'user_id'      => $user->id,
                        'role'         => $user->role ?? 'unknown',
                        'action'       => 'WALLET_MISMATCH',
                        'service_name' => 'wallet',
                        'request_data' => json_encode([
                            'w1_balance' => $user->w1,
                            'ledger_balance' => $ledger->closing_balance
                        ]),
                        'response_data'=> json_encode(['message' => 'Mismatch found']),
                        'ip_address'   => null,
                        'user_agent'   => 'SYSTEM_CRON',
                        'status'       => 'Failed'
                    ]);
                }
            }
        }}
	public function secureLogin(Request $request){
	    $agent = $request->header('User-Agent');
        $allowedAgents = ['Transup/1.0','ImpsguruAppWeb/1.0','Mozilla'];
        $allowed = false;
        foreach ($allowedAgents as $allowedAgent) {
            if (str_contains($agent, $allowedAgent)) {
                $allowed = true;
                break;
            }
        }
        if (!$allowed) {
            return $this->jsonResponse('1000', 'Failed', 'Browser Direct Login Not Allowed To Login','', 422);
        }
		$validator = AuthValidation::secureLogin($request->all());
        if ($validator->fails()) {
            return $this->jsonResponse('1000', 'Failed', $validator->errors(),'', 422);
        }
		$user = User::find(substr($request->username, 5));
    	if ($user->is_active == 0) {
        return ResponseHelper::error("Your account is inactive, contact your admin.", [], 403);
          }
		if($user){
 			$pass = $request->password.env('PASS_KEY');
            if (Hash::check($pass, $user->password)) {
                if($user->two_fa == 'OTP'){
                    $validator = AuthValidation::otpLogin($request->all());
                    if ($validator->fails()) {
                        return $this->jsonResponse('1000', 'Failed', $validator->errors(),'', 422);
                    }
                    $otp = Otp::where('otp_ref',$request->otp_ref)->where('status',1)->where('user_id',$user->id)->first();
                    if(!$otp){
                        return $this->jsonResponse('1001', 'Failed', 'Invalid OTP reference','', 403);
                    }
                    $pass = $request->otp.env('OTP_KEY');
                    if (!Hash::check($pass, $otp['otp'])) {
                        return $this->jsonResponse('1001', 'Failed', 'Invalid OTP','', 403);
                    }
                    $otp->status = 0;
                    $otp->save();
                }elseif($user->two_fa == 'MPIN'){
                    if($request->has('otp')){
                        $validator = AuthValidation::otpLogin($request->all());
                        if ($validator->fails()) {
                            return $this->jsonResponse('1000', 'Failed', $validator->errors(),'', 422);
                        }
                        $otp = Otp::where('otp_ref',$request->otp_ref)->where('status',1)->where('user_id',$user->id)->first();
                        //return $otp;
                        if(!$otp){
                            return $this->jsonResponse('1001', 'Failed', 'Invalid OTP reference','', 403);
                        }
                        $pass = $request->otp.env('OTP_KEY');
                        if (!Hash::check($pass, $otp['otp'])) {
                            return $this->jsonResponse('1001', 'Failed', 'Invalid OTP','', 403);
                        }
                        $otp->status = 0;
                        $otp->save();
                    }else{
                        $validator = AuthValidation::mpinLogin($request->all());
                        if ($validator->fails()) {
                            return $this->jsonResponse('1000', 'Failed', $validator->errors(),'', 422);
                        }
                        $pass = $request->mpin.env('PIN_KEY');
                        if (!Hash::check($pass, $user->mpin)) {
                            return $this->jsonResponse('1001', 'Failed', 'Invalid MPIN','', 403);
                        }
                    }
                    
                }
                //multiple login allowed by client
                // if($user->role != 'api'){
                //     $token = Str::random(60);
                //     //$user->api_token = hash('sha256', $token.env('PASS_KEY'));
                //     $user->api_token = $token;
                //     $user->encryption_key = Crypt::encrypt(Str::random(16));
                //     $user->save();   
                // }else{
                //     $token = $user->api_token;
                // }
                $token = $user->api_token;
                $this->storeLoginDevice($request, $user);
                if($allowed == 'Transup/1.0'){
                    if($user->imei != $request->header('imei-number')){
                        $user->imei = $request->header('imei-number');
                        $user->save();
                    }
                }
                AuditLog::create([
                    'user_id'      => $user->id,
                    'role'         => $user->role,
                    'action'       => 'SECURE_LOGIN',
                    'service_name' => 'auth',
                    'request_data' => json_encode($request->username),
                    'response_data'=> json_encode(['message' => 'Logged In']),
                    'ip_address'   => $request->ip(),
                    'user_agent'   => $request->header('User-Agent'),
                    'status'       => 'Success'
                ]);
                return $this->jsonResponse('1000', 'Success', 'Login Succes', $token, 200);
            }else{
                AuditLog::create([
                    'user_id'      => $user->id,
                    'role'         => $user->role,
                    'action'       => 'SECURE_LOGIN',
                    'service_name' => 'auth',
                    'request_data' => json_encode($request->username),
                    'response_data'=> json_encode(['message' => 'Unauthorized']),
                    'ip_address'   => $request->ip(),
                    'user_agent'   => $request->header('User-Agent'),
                    'status'       => 'Failed'
                ]);
               return $this->jsonResponse('1001', 'Failed', 'Wrong Password', '', 403); 
            }
		}else{
		    AuditLog::create([
                    'user_id'      => null,
                    'role'         => 'guest',
                    'action'       => 'SECURE_LOGIN',
                    'service_name' => 'auth',
                    'request_data' => json_encode($request->username),
                    'response_data'=> json_encode(['message' => 'User Not Registered']),
                    'ip_address'   => $request->ip(),
                    'user_agent'   => $request->header('User-Agent'),
                    'status'       => 'Failed'
                ]);
			return $this->jsonResponse('1001', 'Failed', 'User Not Registered With This User ID', '', 404);
		}
    }
    public function changePassword(Request $request){
		$user = Auth::user();
		$validator = AuthValidation::changePassword($request->all());
        if ($validator->fails()) {
            return $this->jsonResponse('1000', 'Failed', $validator->errors(),'', 422);
        }
		$pass = $request->mpin.env('PIN_KEY');
        if (!Hash::check($pass, $user->mpin)) {
            return $this->jsonResponse('1001', 'Failed', 'Invalid MPIN','', 403);
        }
        $pass = $request->current_password.env('PASS_KEY');
        if (!Hash::check($pass, $user->password)) {
            return $this->jsonResponse('1001', 'Failed', 'Incorrect Old Password', '', 403);
        }
        $token = Str::random(60);
		$user->password = Hash::make($request->new_password.env('PASS_KEY'));
		$user->api_token = ($user->role == 'api') ? $user->api_token : $token;
		$user->save();
		AuditLog::create([
            'user_id'      => $user->id,
            'role'         => $user->role,
            'action'       => 'CHANGE_PASSWORD',
            'service_name' => 'auth',
            'request_data' => json_encode($request->username),
            'response_data'=> json_encode(['message' => 'Password Changed']),
            'ip_address'   => $request->ip(),
            'user_agent'   => $request->header('User-Agent'),
            'status'       => 'Success'
        ]);
		return $this->jsonResponse('1000', 'Success', 'Password Changed Successfully', $token, 200);
	}
	public function forgetPassword(Request $request){
		$validator = AuthValidation::forgetPassword($request->all());
        if ($validator->fails()) {
            return $this->jsonResponse('1000', 'Failed', $validator->errors(),'', 422);
        }
	    if (!str_starts_with($request->username, 'TRANS')) {
        return $this->jsonResponse('1002','Failed','Invalid user id','',422 );
         }
	    $user = User::find(substr($request->username, 5));
		if($user){
		    if($user->is_active == 0){
		        return $this->jsonResponse('1001', 'Failed', 'Deactivated Account Kindly Contact Administrator', '', 403);
		    }
            $pwd = strtoupper(Str::random(2)).Str::random(2).'@'.rand(1000, 9999);
            $token = Str::random(60);
    		$user->api_token = ($user->role == 'api') ? $user->api_token : $token;
    		$user->password = Hash::make($pwd.env('PASS_KEY'));
    		$user->save();
    		$template = Template::where('name','FORGET_PASSWORD')->first();
			$message = str_replace('{$password}', $pwd, $template->message);
		    $data = ["number"=>$user->mobile,"message"=>$message, "tempid"=>$template->temp_id];
		    //return $data;
			$res = Sms::send($data,$user);
			AuditLog::create([
                'user_id'      => $user->id,
                'role'         => $user->role,
                'action'       => 'FORGET_PASSWORD',
                'service_name' => 'auth',
                'request_data' => json_encode($request->username),
                'response_data'=> json_encode(['message' => 'Password Sent']),
                'ip_address'   => $request->ip(),
                'user_agent'   => $request->header('User-Agent'),
                'status'       => 'Success'
            ]);
			return $this->jsonResponse('1000', 'Success', '', $res, 200);
		}else{
		    AuditLog::create([
                'user_id'      => null,
                'role'         => 'guest',
                'action'       => 'FORGET_PASSWORD',
                'service_name' => 'auth',
                'request_data' => json_encode($request->username),
                'response_data'=> json_encode(['message' => 'Unauthorised']),
                'ip_address'   => $request->ip(),
                'user_agent'   => $request->header('User-Agent'),
                'status'       => 'Failed'
            ]);
			return $this->jsonResponse('1001', 'Failed', 'User Not Registered With This Mobile Number', '', 403);
		}
	}
    public function getMobile(Request $request){
        // --- VALIDATION (without AuthValidation) ---
        $validator = Validator::make($request->all(), [
            'username' => 'required',
        ]);
    
        if ($validator->fails()) {
            return $this->jsonResponse('1000', 'Failed', $validator->errors(), '', 422);
        }
        if (!str_starts_with($request->username, 'TRANS')) {
            return $this->jsonResponse('1002', 'Failed', 'Invalid user id', '', 422);
        }
        $userId = substr($request->username, 5);
        $user = User::select('mobile')->find($userId);
    
        if (!$user) {
            return $this->jsonResponse('1003', 'Failed', 'User not found', '', 404);
        }
    
        return $this->jsonResponse('200', 'Success', 'Mobile fetched successfully', $user, 200);
    }
	public function resendOtp(Request $request){
        $validator = AuthValidation::resendOtp($request->all());
        if ($validator->fails()) {
            return $this->jsonResponse('1000', 'Failed', $validator->errors(), '', 422);
        }
        if (!str_starts_with($request->username, 'TRANS')) {
            return $this->jsonResponse('1002','Failed','Invalid user id','',422 );
             }
    	    $user = User::find(substr($request->username, 5));
        if (!$user) {
            AuditLog::create([
                'user_id'      => null,
                'role'         => 'guest',
                'action'       => 'RESEND_OTP',
                'service_name' => 'auth',
                'request_data' => json_encode($request->username),
                'response_data'=> json_encode(['message' => 'User Not Registered']),
                'ip_address'   => $request->ip(),
                'user_agent'   => $request->header('User-Agent'),
                'status'       => 'Failed'
            ]);
            return $this->jsonResponse('1001', 'Failed', 'User Not Registered With This User ID', '', 404);
        }
    
        // Invalidate all previous OTPs for this user
        Otp::where('user_id', $user->id)
            ->where('status', 1)
            ->update(['status' => 0]);
    
        // Generate new OTP
        $otp = Otp::loginOtp($user->id);
    
        // Send OTP via SMS
        $template = Template::where('name', 'MOB_VERIFY')->first();
        $message = str_replace('{$otp}', $otp['otp'], $template->message);
        $data = ["number" => $user->mobile, "message" => $message, "tempid" => $template->temp_id];
        Sms::send($data,$user);
    
        // Log the action
        AuditLog::create([
            'user_id'      => $user->id,
            'role'         => $user->role,
            'action'       => 'RESEND_OTP',
            'service_name' => 'auth',
            'request_data' => json_encode($request->username),
            'response_data'=> json_encode(['message' => 'OTP Sent']),
            'ip_address'   => $request->ip(),
            'user_agent'   => $request->header('User-Agent'),
            'status'       => 'Success'
        ]);
    
        return $this->jsonResponse('1000', 'Success', $otp['otp_ref'], 'OTP', 200);
    }
	public function changeMPin(Request $request){
		$user = Auth::user();
		$validator = AuthValidation::changeMPin($request->all());
        if ($validator->fails()) {
            return $this->jsonResponse('1000', 'Failed', $validator->errors(),'', 422);
        }
        $pass = $request->current_mpin.env('PIN_KEY');
        if (!Hash::check($pass, $user->mpin)) {
            return $this->jsonResponse('1001', 'Failed', 'Invalid Old MPIN','', 403);
        }
	    $user->mpin = Hash::make($request->new_mpin.env('PIN_KEY'));
	    $user->save();
		return $this->jsonResponse('1000', 'Success', 'MPin Successfully Changed', '', 200);
	}
	public function resetMpin(Request $request){
	    $validator = AuthValidation::resetMpin($request->all());
        if ($validator->fails()) {
            return $this->jsonResponse('1000', 'Failed', $validator->errors(),'', 422);
        }
// 		$user = User::find(substr($request->username, 5));
// 		if(!$user){
// 		    return $this->jsonResponse('1001', 'Failed', 'User Not Found With This ID', '', 403);
// 		}
       if (!str_starts_with($request->username, 'TRANS')) {
        return $this->jsonResponse('1002','Failed','Invalid user id','',422 );
         }
	    $user = User::find(substr($request->username, 5));
    	$mpin = rand(100000, 999999);
    	$user->mpin = Hash::make($mpin.env('PIN_KEY'));
    	$user->mpin_retries = 0;
    	$user->save();
    	$template = Template::where('name','RESET_MPIN')->first();
		$message = str_replace('{$mpin}', $mpin, $template->message);
		$data = ["number"=>$user->mobile,"message"=>$message, "tempid"=>$template->temp_id];
    	$res = Sms::send($data,$user);
		return $this->jsonResponse('1000', 'Success', 'M-Pin Sent To Registered Mobile Number', $res, 200);
	}
	public function changeTwoFa(){
	    $user = Auth::user();
	    if($user->two_fa == 'OTP'){
	        $user->two_fa = 'MPIN';
	    }elseif($user->two_fa == 'MPIN'){
	        $user->two_fa = 'OTP';
	    }
	    $user->save();
	    return $this->jsonResponse('1000', 'Success', 'Two Factor Setting Changed Successfully', '', 200);
	}
    public function changeRole(Request $request){
            $user = Auth::user();
        
            if (!in_array($user->role, ['sadm'])) {
                return ResponseHelper::error("Unauthorized", [], 401);
            }
          $validator = Validator::make($request->all(), [
            'user_id'   => 'required|exists:users,id',
            'parent_id' => 'sometimes|exists:users,id',
            'role'      => 'required|string|in:di,ret,md,dd,adm,sadm,asm,zsm',
        ]);
    
        if ($validator->fails()) {
            return ResponseHelper::error("Validation Failed", $validator->errors(), 422);
        }
            if (!$request->role) {
                return ResponseHelper::error("Role field is required", [], 403);
            }
        
            $allowedRoles = ['di', 'ret', 'md', 'dd', 'adm', 'sadm', 'asm', 'zsm'];
            if (!in_array($request->role, $allowedRoles)) {
                return ResponseHelper::error("Invalid role provided", [], 403);
            }
        
            if ($user->id == $request->user_id) {
                return ResponseHelper::error("You cannot change your own role", [], 403);
            }
        
            $userNew = User::find($request->user_id);
            if (!$userNew) {
                return ResponseHelper::error("User not found", [], 403);
            }
        
            if ($userNew->role === $request->role) {
                return ResponseHelper::error("User already has this role", [], 409);
            }
        
            $userNew->role = $request->role;
            $userNew->parent = $request->parent_id;
            $userNew->save();
        
            return ResponseHelper::success("Role updated successfully", $userNew, 200);
        }
    public function changeParent(Request $request)
    {
        $user = Auth::user();
    
        if (!in_array($user->role, ['sadm','adm'])) {
            return ResponseHelper::error("Unauthorized", [], 401);
        }
          $validator = Validator::make($request->all(), [
            'user_id'   => 'required|exists:users,id',
            'parent_id' => 'required|exists:users,id',
        ]);

    if ($validator->fails()) {
        return ResponseHelper::error("Validation Failed", $validator->errors(), 422);
    }
        if ($user->id == $request->user_id) {
            return ResponseHelper::error("You cannot change your own role", [], 403);
        }
        $userNew = User::find($request->user_id);
        if (!$userNew) {
            return ResponseHelper::error("User not found", [], 403);
        }
    
        if ($userNew->role === $request->role) {
            return ResponseHelper::error("User already has this role", [], 409);
        }
        $userNew->parent = $request->parent_id;
        $userNew->save();
    
        return ResponseHelper::success("Parent updated successfully", $userNew, 200);
    }
	public function initiateChangeNumber(Request $request){
	    $validator = AuthValidation::initiateChangeNumber($request->all());
        if ($validator->fails()) {
            return $this->jsonResponse('1001', 'Failed', $validator->errors(),'', 422);
        }
	    $user = Auth::user();
	    if($request->new_number == $user->mobile){
	        return $this->jsonResponse('1001', 'Failed', 'New Number Must Be Different From Existing Number','', 422);
	    }
	    //send otp to old number
	    $old_otp = Otp::loginOtp($user->id);
		$template = Template::where('name','MOB_VERIFY')->first();
		$message = str_replace('{$otp}', $old_otp['otp'], $template->message);
	    $data = ["number"=>$user->mobile,"message"=>$message, "tempid"=>$template->temp_id];
		Sms::send($data,$user);
		//send otp to new number
		$new_otp = Otp::loginOtp($user->id);
		$template = Template::where('name','MOB_VERIFY')->first();
		$message = str_replace('{$otp}', $new_otp['otp'], $template->message);
	    $data = ["number"=>$request->new_number,"message"=>$message, "tempid"=>$template->temp_id];
		Sms::send($data,$user);
		$otp_ref_data['old_otp_ref'] = $old_otp['otp_ref'];
		$otp_ref_data['new_otp_ref'] = $new_otp['otp_ref'];
	    return $this->jsonResponse('1000', 'Success', 'Two Factor Setting Changed Successfully', $otp_ref_data, 200);
	}
	public function verifyChangeNumber(Request $request){
	    $validator = AuthValidation::verifyChangeNumber($request->all());
        if ($validator->fails()) {
            return $this->jsonResponse('1001', 'Failed', $validator->errors(),'', 422);
        }
	    $user = Auth::user();
	    $old_otp = Otp::where('otp_ref',$request->old_otp_ref)->where('status',1)->where('user_id',$user->id)->first();
        //return $otp;
        if(!$old_otp){
            return $this->jsonResponse('1001', 'Failed', 'Invalid Old OTP reference','', 403);
        }
        $pass = $request->old_mobile_otp.env('OTP_KEY');
        // return $pass;
        if (!Hash::check($pass, $old_otp->otp)) {
            return $this->jsonResponse('1001', 'Failed', 'Invalid Old OTP','', 403);
        }
        
        $new_otp = Otp::where('otp_ref',$request->new_otp_ref)->where('status',1)->where('user_id',$user->id)->first();
        //return $otp;
        if(!$new_otp){
            return $this->jsonResponse('1001', 'Failed', 'Invalid New OTP reference','', 403);
        }
        $pass = $request->new_mobile_otp.env('OTP_KEY');
        if (!Hash::check($pass, $new_otp->otp)) {
            return $this->jsonResponse('1001', 'Failed', 'Invalid New OTP','', 403);
        }
        $user->mobile = $request->new_number;
        $user->save();
        $old_otp->status = 0;
        $old_otp->save();
        $new_otp->status = 0;
        $new_otp->save();
        return $this->jsonResponse('1000', 'Success', 'Mobile Number Changed Successfully', $user, 200);
	}
    public function storeLoginDevice($request, $user){
		$userDevice = UserDevice::where('user_id', $user->id)
								->where('device', $request->header('User-Agent'))
								->where('ip', $request->ip())
								->first();
		if(!$userDevice){
			$newDevice = new UserDevice();
			$newDevice->user_id = $user->id;
			$newDevice->device = $request->header('User-Agent');
			$newDevice->ip = $request->ip();
			$newDevice->save();
			return $this->jsonResponse('1000', 'Success', 'New Device Stored', '', 200);
		}
		return $this->jsonResponse('1000', 'Success', 'Old Device Used', '', 200);
	}
	public function Logout(){
		$user = Auth::user();
// 		$token = Str::random(30);
// 		$user->api_token = ($user->role == 'api') ? $user->api_token : $token;
// 		$user->encryption_key = Crypt::encrypt(Str::random(1));
// 		$user->save();
		return $this->jsonResponse('1000', 'Success', 'You have Logged Out Successfully', '', 200);
	}
}
