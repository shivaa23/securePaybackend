<?php
namespace App\Modules\Payout\Models;

use Illuminate\Database\Eloquent\Model;
use Validator;
use Auth;

class SenderValidation extends Model
{
    public static function registerSender($request)
    {
        $user = Auth::user();
    
        return Validator::make($request, [
            'sender_name'   => 'required|string|min:3|max:50|regex:/^[a-zA-Z\s]+$/u',
            'mobile_number' => 'required|numeric|regex:/^[6-9][0-9]{9}$/u|unique:payout_senders,mobile_number',
            // 'email'         => 'required|email|unique:payout_senders,email',
        ], [
            // sender_name messages
            'sender_name.required' => 'Sender name is required.',
            'sender_name.string'   => 'Sender name must be a string.',
            'sender_name.min'      => 'Sender name should be at least 3 characters.',
            'sender_name.max'      => 'Sender name should not exceed 50 characters.',
            'sender_name.regex'    => 'Sender name should only contain letters and spaces.',
    
            // mobile_number messages
            'mobile_number.required' => 'Mobile number is required.',
            'mobile_number.numeric'  => 'Mobile number must be numeric.',
            'mobile_number.regex'    => 'Enter a valid 10-digit Indian mobile number.',
            'mobile_number.unique'   => 'This mobile number is already registered.',
    
            // // email messages
            // 'email.required' => 'Email is required.',
            // 'email.email'    => 'Enter a valid email address.',
            // 'email.unique'   => 'This email is already registered.',
        ]);
    }


    public static function verifySender($request)
    {
        return Validator::make($request, [
            'mobile_number'       => 'required|integer|exists:payout_senders,mobile_number',
            'otp'      => 'required|digits:6',
            'otp_ref'  => 'required|integer|min:6',
        ], [
            'id.required' => 'Sender ID is required.',
            'id.integer'  => 'Sender ID must be a valid number.',
            'id.exists'   => 'Sender ID does not exist.',

            'otp.required' => 'OTP is required.',
            'otp.digits'   => 'OTP must be exactly 6 digits.',

            'otp_ref.required' => 'OTP reference is required.',
            'otp_ref.integer'   => 'OTP reference must be a string.',
            'otp_ref.min'      => 'OTP reference must be at least 6 characters.',
            
        ]);
    }
    //     public static function verifySender($request){
    //     return Validator::make($request, [
    //         'mobile_number'       => 'required|integer|exists:payout_senders,mobile_number',
    //         'otp'      => 'required|digits:6',
    //         'otp_ref'  => 'required|integer|min:6',
    //     ], [
    //         'id.required' => 'Sender ID is required.',
    //         'id.integer'  => 'Sender ID must be a valid number.',
    //         'id.exists'   => 'Sender ID does not exist.',

    //         'otp.required' => 'OTP is required.',
    //         'otp.digits'   => 'OTP must be exactly 6 digits.',

    //         'otp_ref.required' => 'OTP reference is required.',
    //         'otp_ref.integer'   => 'OTP reference must be a string.',
    //         'otp_ref.min'      => 'OTP reference must be at least 6 characters.',
            
    //     ]);
    // }

    /**
     * Schema for Register Sender
     */
   public static function registerSenderSchema()
    {
        return [
            "formName" => "Register Sender",
            "fields" => [
                [
                    "name" => "sender_name",
                    "label" => "Sender Name",
                    "type" => "text",
                    "placeholder" => "Enter sender name",
                    "required" => true,
                    "validation" => [
                        "type"  => "string",
                        "min"   => 3,
                        "max"   => 50,
                        "regex" => "^[a-zA-Z\s]+$"
                    ]
                ],
                [
                    "name" => "mobile_number",
                    "label" => "Mobile Number",
                    "type" => "tel",
                    "placeholder" => "Enter 10-digit mobile number",
                    "required" => true,
                    "validation" => [
                        "type"   => "string",
                        "regex"  => "^[6-9][0-9]{9}$",
                        "unique" => "payout_senders,mobile_number"
                    ]
                ],
                // [
                //     "name" => "email",
                //     "label" => "Email",
                //     "type" => "email",
                //     "placeholder" => "Enter email address",
                //     "required" => true,
                //     "validation" => [
                //         "type"   => "email",
                //         "unique" => "payout_senders,email"
                //     ]
                // ]
            ]
        ];
    }


    /**
     * Schema for Verify Sender
     */
    public static function verifySenderSchema()
    {
        return [
            "formName" => "Verify Sender",
            "fields" => [
              
                [
                    "name" => "otp",
                    "label" => "OTP",
                    "type" => "number",
                    "placeholder" => "Enter 6-digit OTP",
                    "required" => true,
                    "validation" => [
                        "type" => "digits",
                        "length" => 6
                    ]
                ]
            ]
        ];
    }
}
