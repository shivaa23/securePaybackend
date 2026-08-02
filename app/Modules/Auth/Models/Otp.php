<?php

namespace App\Modules\Auth\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Hash;
use App\Modules\Auth\Models\AuthValidation;
use App\Helper\ResponseHelper;
use Auth;
class Otp extends Model 
{

    protected $fillable = [
    ];

    protected $hidden = [  ];
    
    public static function loginOtp($user_id){
        $newOtp = rand(100000, 999999);
        $otp_ref = rand(1000000, 9999999);
        $otp = new Otp();
        $otp->user_id = $user_id;
        $otp->otp_ref = $otp_ref;
		$otp->otp = Hash::make($newOtp.env('OTP_KEY'));
		$otp->save();
		$data['otp_ref'] = $otp_ref;
		$data['otp'] = $newOtp;
        return $data;
    }
     public static function verifyOtp($request)
    {
    
    
        $user = Auth::user();
        $otp = Otp::where('otp_ref', $request->otp_ref)
                  ->where('user_id', $user->id)
                  ->where('status', 1)
                  ->first();
    
        if (!$otp) {
            return [
                'success' => false,
                'message' => 'Invalid OTP reference',
            ];
        }
    
        $pass = $request->otp . env('OTP_KEY');
        if (!Hash::check($pass, $otp->otp)) {
            return [
                'success' => false,
                'message' => 'Invalid OTP',
            ];
        }
    
        $otp->status = 0;
        $otp->save();
    
        return [
            'success' => true,
            'message' => 'OTP Verified Successfully',
        ];
    }
public static function verifyMpin($mpin)
{
    $user = Auth::user();
    $pass = $mpin . env('PIN_KEY');

    if (!Hash::check($pass, $user->mpin)) {
        return false; // invalid mpin
    }

    return true; // valid mpin
}


    
}