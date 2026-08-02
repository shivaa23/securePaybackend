<?php

namespace App\Modules\Signup\Models;

use Illuminate\Database\Eloquent\Model;
use App\Modules\Utils\getModes;
use App\Modules\Bank\Models\Bank;
use App\Modules\User\Models\User; 
use Validator;
class SignupValidation extends Model
{
    public static function signup($request)
    {
        return Validator::make($request, [
            // User details
            'user.name'   => 'required|string|max:255',
            'user.email'  => 'required|string|email|max:255|unique:users,email',
            'user.pan'   => 'required|string|max:255',
            'user.gst'  => 'sometimes|string|max:255|unique:users,gst',
            'user.mobile' => [
                'required',
                'regex:/^[6-9][0-9]{9}$/',
            ],
            'user.parent' => 'nullable|integer|exists:users,id',
    
            // Business details
            'business.business_name' => 'nullable|string|max:255',
            'business.entity_type'   => 'nullable|string|max:100',
            // 'business.gst_number'    => 'nullable|string|max:50',
            // 'business.pan_number'    => 'nullable|string|max:50',
    
            // Business Contact
            // 'business_contact.business_email'  => 'nullable|string|email|max:255',
            // 'business_contact.business_mobile' => [
            //     'nullable',
            //     'regex:/^[6-9][0-9]{9}$/'
            // ],
    
            // Business Address
            'business_address.address_line1' => 'nullable|string|max:255',
            'business_address.address_line2' => 'nullable|string|max:255',
            'business_address.city'          => 'nullable|string|max:100',
            'business_address.state'         => 'nullable|string|max:100',
            'business_address.pincode'       => 'nullable|string|max:20',
    
            // Business Identification
            // 'business_identification.pan_number' => 'required|string|max:50',
            // 'business_identification.gst_number' => 'nullable|string|max:50',
    
        ], [
            // Custom messages
            'user.email.unique'       => 'This email is already registered.',
            'user.mobile.required'    => 'Mobile number is required.',
            'user.mobile.regex'       => 'Enter a valid 10-digit mobile number.',
            'user.mobile.unique'      => 'This mobile number is already registered.',
        ]);
    }
        public static function editUser($request)
    {
        return Validator::make($request, [
            // User details
            
            'id' =>'required|integer|unique:users,id',
            'user.name'   => 'sometimes|string|max:255',
            'user.email'  => 'sometimes|string|email|max:255|unique:users,email',
              'user.pan'   => 'sometimes|string|max:255|unique:users,pan',
            'user.gst'  => 'sometimes|string|max:255|unique:users,gst',
            'user.mobile' => [
                'sometimes',
                'regex:/^[6-9][0-9]{9}$/',
            ],
            // 'user.parent' => 'nullable|integer|exists:users,id',
    
            // Business details
            'business.business_name' => 'nullable|string|max:255',
            'business.entity_type'   => 'nullable|string|max:100',
            'business.gst_number'    => 'nullable|string|max:50',
            'business.pan_number'    => 'nullable|string|max:50',
    
            // Business Contact
            // 'business_contact.business_email'  => 'nullable|string|email|max:255',
            // 'business_contact.business_mobile' => [
            //     'nullable',
            //     'regex:/^[6-9][0-9]{9}$/'
            // ],
    
            // Business Address
            'business_address.address_line1' => 'nullable|string|max:255',
            'business_address.address_line2' => 'nullable|string|max:255',
            'business_address.city'          => 'nullable|string|max:100',
            'business_address.state'         => 'nullable|string|max:100',
            'business_address.pincode'       => 'nullable|string|max:20',
    
            // Business Identification
            // 'business_identification.pan_number' => 'required|string|max:50',
            // 'business_identification.gst_number' => 'nullable|string|max:50',
    
        ], [
            // Custom messages
            'user.email.unique'       => 'This email is already registered.',
            'user.mobile.required'    => 'Mobile number is required.',
            'user.mobile.regex'       => 'Enter a valid 10-digit mobile number.',
            'user.mobile.unique'      => 'This mobile number is already registered.',
        ]);
    }



    public static function getSignupSchema()
    {
        // $roles = [
        //     ['label' => 'Super Admin', 'value' => 'sadm'],
        //     ['label' => 'Admin', 'value' => 'adm'],
        //     ['label' => 'Zonal Sales Manager', 'value' => 'zsm'],
        //     ['label' => 'ASM', 'value' => 'asm'],
        //     ['label' => 'MD', 'value' => 'md'],
        //     ['label' => 'AD', 'value' => 'ad'],
        //     ['label' => 'Retailer', 'value' => 'ret'],
        //     ['label' => 'Distributor', 'value' => 'dd']
        // ];
    
        return [
            "formName" => "Signup Form",
            "fields" => [
                // User Details
                [
                    "name" => "user.name",
                    "label" => "Full Name",
                    "type" => "text",
                    "placeholder" => "Enter full name",
                    "required" => true,
                    "validation" => ["type" => "string", "maxLength" => 255]
                ],
                [
                    "name" => "user.email",
                    "label" => "Email",
                    "type" => "email",
                    "placeholder" => "Enter email",
                    "required" => true,
                    "validation" => ["type" => "string", "format" => "email", "unique" => "users,email"]
                ],
                [
                    "name" => "user.mobile",
                    "label" => "Mobile Number",
                    "type" => "text",
                    "placeholder" => "Enter 10-digit mobile number",
                    "required" => true,
                    "validation" => ["type" => "string", "regex" => "/^[6-9][0-9]{9}$/", "unique" => "users,mobile"]
                ],
                
                [
                    "name" => "user.pan",
                    "label" => " User PAN Number",
                    "type" => "text",
                    "placeholder" => "Enter PAN ",
                    "required" => false,
                    "validation" => ["type" => "string", "maxLength" => 50]
                ],
                [
                    "name" => "user.gst",
                    "label" => " User GST ",
                    "type" => "text",
                    "placeholder" => "Enter GST number",
                    "required" => false,
                    "validation" => ["type" => "string", "maxLength" => 50]
                ],
                // Business Details
                [
                    "name" => "business.business_name",
                    "label" => "Shop Name",
                    "type" => "text",
                    "placeholder" => "Enter business name",
                    "required" => false,
                    "validation" => ["type" => "string", "maxLength" => 255]
                ],
                // [
                //     "name" => "business.pan_number",
                //     "label" => "PAN Number",
                //     "type" => "text",
                //     "placeholder" => "Enter PAN number",
                //     "required" => false,
                //     "validation" => ["type" => "string", "maxLength" => 50]
                // ],
                // [
                //     "name" => "business.gst_number",
                //     "label" => "GST Number",
                //     "type" => "text",
                //     "placeholder" => "Enter GST number",
                //     "required" => false,
                //     "validation" => ["type" => "string", "maxLength" => 50]
                // ],
    
                // Business Contact
                
                // Business Address
                [
                    "name" => "business_address.address_line1",
                    "label" => "Address Line 1",
                    "type" => "text",
                    "placeholder" => "Enter address line 1",
                    "required" => false,
                    "validation" => ["type" => "string", "maxLength" => 255]
                ],
                [
                    "name" => "business_address.address_line2",
                    "label" => "Address Line 2",
                    "type" => "text",
                    "placeholder" => "Enter address line 2",
                    "required" => false,
                    "validation" => ["type" => "string", "maxLength" => 255]
                ],
                [
                    "name" => "business_address.city",
                    "label" => "City",
                    "type" => "text",
                    "placeholder" => "Enter city",
                    "required" => false,
                    "validation" => ["type" => "string", "maxLength" => 100]
                ],
                [
                    "name" => "business_address.state",
                    "label" => "State",
                    "type" => "text",
                    "placeholder" => "Enter state",
                    "required" => false,
                    "validation" => ["type" => "string", "maxLength" => 100]
                ],
                [
                    "name" => "business_address.pincode",
                    "label" => "Pincode",
                    "type" => "text",
                    "placeholder" => "Enter pincode",
                    "required" => false,
                    "validation" => ["type" => "string", "maxLength" => 20]
                ],
            ]
        ];
    }

}
