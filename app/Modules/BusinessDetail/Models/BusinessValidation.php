<?php

namespace App\Modules\BusinessDetail\Models;

use Illuminate\Support\Facades\Validator;
use Auth;
class BusinessValidation 
{
    /** -------------------- BASIC -------------------- **/
    public static function createBasicRequest($request)
    {
        return Validator::make($request, [
            'business_name' => 'required|string|max:255',
            'business_type' => 'sometimes|string|max:100',
            'industry' => 'nullable|string|max:100',
            'date_of_incorporation' => 'nullable|date',
        ]);
    }

    public static function basicSchema()
    {
        return [
            "formName" => "Business Basic Details",
            "fields" => [
                ["name" => "business_name", "label" => "Business Name", "type" => "text", "required" => true],
            //     ["name" => "business_type", "label" => "Business Type", "type" => "text", "required" => true],
            //     ["name" => "industry", "label" => "Industry", "type" => "text", "required" => false],
            //     ["name" => "date_of_incorporation", "label" => "Date of Incorporation", "type" => "date", "required" => false],
            ]
        ];
    }

    /** -------------------- CONTACT -------------------- **/
    public static function createContactRequest($request)
    {
        return Validator::make($request, [
           
            'mail' => 'nullable|email|max:255',
            'business_mobile' => 'nullable|regex:/^[6-9][0-9]{9}$/',
            'alternate_mobile' => 'nullable|regex:/^[6-9][0-9]{9}$/',
            'website_url' => 'nullable|string|max:255',
        ], [
            'business_mobile.regex' => 'Enter a valid 10-digit business mobile number.',
            'alternate_mobile.regex' => 'Enter a valid 10-digit alternate mobile number.',
        ]);
    }

    public static function contactSchema()
    {
        return [
            "formName" => "Business Contact Details",
            "fields" => [
                ["name" => "gmail", "label" => "Email", "type" => "email", "required" => false],
                ["name" => "mobile", "label" => "Mobile number", "type" => "text", "required" => false],
                // ["name" => "alternate_mobile", "label" => "Alternate Mobile", "type" => "text", "required" => false],
                // ["name" => "website_url", "label" => "Website URL", "type" => "text", "required" => false],
            ]
        ];
    }

    /** -------------------- ADDRESS -------------------- **/
    public static function createAddressRequest($request)
    {
        return Validator::make($request, [
          
            'address_line1' => 'required|string|max:255',
            'address_line2' => 'nullable|string|max:255',
            'city' => 'required|string|max:100',
            'state' => 'required|string|max:100',
            'pincode' => 'required|string|max:10',
            // 'country' => 'required|string|max:100',
        ]);
    }

    public static function addressSchema()
    {
        return [
            "formName" => "Business Address Details",
            "fields" => [
                ["name" => "address_line1", "label" => "Address Line 1", "type" => "text", "required" => true],
                ["name" => "address_line2", "label" => "Address Line 2", "type" => "text", "required" => false],
                ["name" => "city", "label" => "City", "type" => "text", "required" => true],
                ["name" => "state", "label" => "State", "type" => "text", "required" => true],
                ["name" => "pincode", "label" => "Pincode", "type" => "text", "required" => true],
                // ["name" => "country", "label" => "Country", "type" => "text", "required" => true],
            ]
        ];
    }

    /** -------------------- IDENTIFICATION -------------------- **/
    public static function createIdentificationRequest($request)
    {
        return Validator::make($request, [
           
            'pan_number' => 'nullable|string|max:50',
            'gst_number' => 'nullable|string|max:50',
            // 'udyam_number' => 'nullable|string|max:50',
            // 'cin_number' => 'nullable|string|max:50',
            // 'tan_number' => 'nullable|string|max:50',
        ]);
    }

    public static function identificationSchema()
    {
        return [
            "formName" => "Business Identification Details",
            "fields" => [
                ["name" => "pan_number", "label" => "PAN Number", "type" => "text", "required" => false],
                ["name" => "gst_number", "label" => "GST Number", "type" => "text", "required" => false],
                // ["name" => "udyam_number", "label" => "Udyam Number", "type" => "text", "required" => false],
                // ["name" => "cin_number", "label" => "CIN Number", "type" => "text", "required" => false],
                // ["name" => "tan_number", "label" => "TAN Number", "type" => "text", "required" => false],
            ]
        ];
    }

    /** -------------------- BANK -------------------- **/
    public static function createBankRequest($request)
    {
        return Validator::make($request, [
          
            'account_holder_name' => 'required|string|max:255',
            // 'bank_name' => 'required|string|max:255',
            // 'branch_name' => 'required|string|max:255',
            'ifsc_code' => 'required|string|max:20',
            'account_number' => 'required|string|max:30',
            // 'upi_id' => 'nullable|string|max:100',
        ]);
    }

    public static function bankSchema()
    {
        return [
            "formName" => "Business Bank Details",
            "fields" => [
                ["name" => "account_holder_name", "label" => "Account Holder Name", "type" => "text", "required" => true],
                // ["name" => "bank_name", "label" => "Bank Name", "type" => "text", "required" => true],
                // ["name" => "branch_name", "label" => "Branch Name", "type" => "text", "required" => true],
                ["name" => "ifsc_code", "label" => "IFSC Code", "type" => "text", "required" => true],
                ["name" => "account_number", "label" => "Account Number", "type" => "text", "required" => true],
                // ["name" => "upi_id", "label" => "UPI ID", "type" => "text", "required" => false],
            ]
        ];
    }

    /** -------------------- DOCUMENTS -------------------- **/
    public static function createDocumentsRequest($request)
    {
        return Validator::make($request, [
           
            'gst_certificate' => 'nullable|string',
            'udyam_certificate' => 'nullable|string',
            'shop_license' => 'nullable|string',
            'rent_agreement' => 'nullable|string',
            'electricity_bill' => 'nullable|string',
        ]);
    }

    public static function documentsSchema()
    {
        return [
            "formName" => "Business Documents",
            "fields" => [
                ["name" => "gst_certificate", "label" => "GST Certificate", "type" => "file", "required" => false],
                // ["name" => "udyam_certificate", "label" => "Udyam Certificate", "type" => "file", "required" => false],
                ["name" => "shop_license", "label" => "Shop Image", "type" => "file", "required" => false],
                ["name" => "rent_agreement", "label" => "Rent Agreement", "type" => "file", "required" => false],
                ["name" => "electricity_bill", "label" => "Electricity Bill", "type" => "file", "required" => false],
            ]
        ];
    }

    /** -------------------- KYC -------------------- **/
//       public static function createKycRequest($request)
// {
//     return Validator::make($request, [
//         'user_id' => ['sometimes', 'integer', 'exists:users,id'],

//         // File validations
//         'aadhaar_front' => ['required', 'file', 'mimes:jpeg,jpg,png', 'max:2048'], // max in KB
//         'aadhaar_back'  => ['required', 'file', 'mimes:jpeg,jpg,png', 'max:2048'],
//         'pan_card'      => ['required', 'file', 'mimes:jpeg,jpg,png', 'max:2048'],
//         'photo'         => ['required', 'file', 'mimes:jpeg,jpg,png', 'max:2048'],
//         'shop_image'    => ['required', 'file', 'mimes:jpeg,jpg,png', 'max:2048'],
//     ]);
// }

public static function createKycRequest($request)
{
    $rules = [
        'user_id' => ['sometimes', 'integer', 'exists:users,id'],
    ];

    // ✅ Required document base keys
    $docKeys = [
        'aadhaar_front',
        'aadhaar_back',
        'pan_card',
        'photo',
        'shop_image',
    ];

    foreach ($docKeys as $key) {
        if (request()->hasFile($key)) {
            $rules[$key] = 'required|file|mimes:jpeg,jpg,png|max:2048';
        }
    }

    foreach ($request as $key => $value) {

        foreach ($docKeys as $docKey) {

            // match: aadhaar_front1, aadhaar_front2, pan_card3 ...
            if (preg_match('/^' . $docKey . '\d+$/', $key)) {
                $rules[$key] = 'required|string';
            }
        }
    }

    return Validator::make($request, $rules);
}

/**
 * Calculate approximate size of base64 string in MB
 */
    private static function base64SizeInMB($base64)
    {
    
        if (strpos($base64, ',') !== false) {
            $base64 = explode(',', $base64)[1];
        }
        $sizeInBytes = (strlen($base64) * 3) / 4;
        return $sizeInBytes / 1024 / 1024; 
    }

    public static function kycSchema()
    {
        return [
            "formName" => "Business KYC",
            "fields" => [
                ["name" => "aadhaar_front", "label" => "Aadhaar Front", "type" => "file", "required" => false],
                ["name" => "aadhaar_back", "label" => "Aadhaar Back", "type" => "file", "required" => false],
                ["name" => "pan_card", "label" => "PAN Card", "type" => "file", "required" => false],
                ["name" => "photo", "label" => "Photo", "type" => "file", "required" => false],
                ["name" => "shop_image", "label" => "Shop Image", "type" => "file", "required" => false],
            ]
        ];
    }

    /** -------------------- METADATA / STATUS -------------------- **/
    public static function createStatusRequest($request)
    {
        return Validator::make($request, [
            'user_id' => 'required|integer|exists:users,id',
            'status' => 'required|in:active,inactive,suspended',
        ]);
    }
        public static function getByType($request)
    {
        return Validator::make($request, [
            'type' => 'required|string|in:basic,contact,address,identification,bank,documents,kyc',
        ]);
    }

    // public static function validateType($request)
    // {
    //     $user = Auth::user();
    
    //     $rules = [
    //         'type' => 'required|string|in:basic,contact,address,identification,bank,documents,kyc',
    //     ];
    
    //     $messages = [
    //         'type.required' => 'Type field is required',
    //         'type.in'       => 'Invalid type provided',
    //     ];
    
    //     // If admin, user_id is required and must exist
    //     if (in_array($user->role, ['adm', 'sadm'])) {
    //         $rules['user_id'] = 'required|exists:users,id';
    //         $messages['user_id.required'] = 'User ID is required for admin';
    //         $messages['user_id.exists'] = 'User not found';
    //     }
    
    //     return Validator::make($request, $rules, $messages);
    // }
public static function validateType($request)
{
    $user = Auth::user();

    $rules = [
        'type' => 'required|string|in:basic,contact,address,identification,bank,documents,kyc,user_details',
    ];

    $messages = [
        'type.required' => 'Type field is required',
        'type.in'       => 'Invalid type provided',
    ];

    // If admin, user_id is required
    if (in_array($user->role, ['adm', 'sadm'])) {
        $rules['user_id'] = 'required|exists:users,id';
        $messages['user_id.required'] = 'User ID is required for admin';
        $messages['user_id.exists'] = 'User not found';
    }

    // Optional (sometimes) KYC images — only validate if provided
    if (isset($request['type']) && $request['type'] === 'kyc') {
        $docKeys = ['aadhaar_front', 'aadhaar_back', 'shop_image', 'pan_card', 'photo'];

        foreach ($docKeys as $docKey) {
            $rules[$docKey] = 'sometimes|image|mimes:jpg,jpeg,png|max:2048';

            $messages["$docKey.image"] = ucfirst(str_replace('_', ' ', $docKey)) . ' must be an image';
            $messages["$docKey.mimes"] = ucfirst(str_replace('_', ' ', $docKey)) . ' must be a jpg, jpeg, or png';
            $messages["$docKey.max"]   = ucfirst(str_replace('_', ' ', $docKey)) . ' cannot exceed 2MB';
        }
    }

    return Validator::make($request, $rules, $messages);
}

    /**
     * Validate getAllByUser request
     */
    public static function validateUserId($request)
    {
        return Validator::make($request, [
            'id' => 'required|integer|exists:users,id',
        ], [
            'id.required' => 'User ID is required',
            'id.integer'  => 'User ID must be an integer',
            'id.exists'   => 'User not found',
        ]);
    }
    public static function approveReject($request)
    {
        return Validator::make($request, [
            'user_id' => 'required|exists:users,id',
            'action'  => 'required|in:approve,reject'
        ], [
            'user_id.required' => 'User ID is required',
            'user_id.exists'   => 'User not found',
            'action.required'  => 'Action is required',
            'action.in'        => 'Action must be either approve or reject'
        ]);
    }



    /**
     * Validate update fields dynamically for updateByType
     */
    public static function validateUpdateFields($request, $fillableFields = [])
    {
        $rules = [];
        foreach ($request as $key => $value) {
            if (in_array($key, $fillableFields)) {
                $rules[$key] = 'nullable';
            }
        }

        return Validator::make($request, $rules);
    }
    public static function statusSchema()
    {
        return [
            "formName" => "Business Status",
            "fields" => [
                ["name" => "status", "label" => "Status", "type" => "dropdown", "required" => true, "options" => ["active", "inactive", "suspended"]],
            ]
        ];
    }
}
