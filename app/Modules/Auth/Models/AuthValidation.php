<?php
    namespace App\Modules\Auth\Models;
use Illuminate\Database\Eloquent\Model;
use Validator;

class AuthValidation extends Model
{
    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
	//
    ];
    
    public static function signIn($request){
        return Validator::make($request, [
            'username' => 'required|string|max:10',
            'password' => 'required|string|max:16'
        ]);
	}
	
	public static function approveQr($request){
	    return Validator::make($request, [
            'token' => 'required|string',
        ]);
	}
	
	public static function secureLogin($request){
        return Validator::make($request, [
            'username' => 'required|string|max:10',
            'password' => 'required|string|max:16'
        ]);
	}
	
		public static function resendOtp($request){
        return Validator::make($request, [
            'username' => 'required|string|max:10',
           
        ]);
	}
	public static function otpLogin($request){
        return Validator::make($request, [
            'otp_ref' => 'required|numeric|digits:7',
            'otp' => 'required|numeric|digits:6'
        ]);
	}
	public static function verifyOtp($request)
    {
        return Validator::make($request, [
            'otp.required' => 'OTP is required.',
            'otp.digits'   => 'OTP must be exactly 6 digits.',

            'otp_ref.required' => 'OTP reference is required.',
            'otp_ref.integer'   => 'OTP reference must be a string.',
            'otp_ref.min'      => 'OTP reference must be at least 6 characters.',
            
        ]);
    }
	public static function mpinLogin($request){
        return Validator::make($request, [
            'mpin' => 'required|numeric|digits:6'
        ]);
	}
	
	public static function changePassword($request){
        return Validator::make($request, [
            'current_password' => 'required|string|max:16',
            'new_password'     => 'required|string|min:8|max:16|confirmed',
            'mpin' => 'required|numeric|digits:6'
        ]);
    }
    
    public static function forgetPassword($request){
        return Validator::make($request, [
            'username' => 'required|string|max:10'
        ]);
	}
	
	public static function changeMPin($request){
        return Validator::make($request, [
            'current_mpin' => 'required|numeric|digits:6',
            'new_mpin'     => 'required|numeric|digits:6|confirmed'
        ]);
    }
    
    public static function resetMPin($request){
        return Validator::make($request, [
            'username' => 'required|string|max:10'
        ]);
	}
	
	public static function initiateChangeNumber($request){
        return Validator::make($request, [
            'new_number' => 'required|numeric|digits:10'
        ]);
	}
	
	public static function verifyChangeNumber($request){
        return Validator::make($request, [
            'new_number' => 'required|numeric|digits:10',
            'new_otp_ref' => 'required|numeric|digits:7',
            'new_mobile_otp' => 'required|numeric|digits:6',
            'old_number' => 'required|numeric|digits:10',
            'old_otp_ref' => 'required|numeric|digits:7',
            'old_mobile_otp' => 'required|numeric|digits:6'
        ]);
	}
	
}