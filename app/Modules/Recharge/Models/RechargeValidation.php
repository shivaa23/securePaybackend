<?php

namespace App\Modules\Recharge\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;
use Auth;

class RechargeValidation extends Model
{
    /**
     * Validate recharge plan data
     */
    public static function recharge($data)
    {
        $user = Auth::user();
        $operatorRule = Rule::exists('services', 'id')
            ->where(function ($query) use ($user) {
                // Always check global is_active
                $query->where('is_active', 1);
        
                // Role-based check
                if ($user->role === 'api') {
                    $query->where('is_active_api', 1);
                } else {
                    $query->where('is_active_users', 1);
                }
            });
        
        return Validator::make($data, [
         
            'client_ref'    => 'required|string|max:20|unique:recharge_txns,client_ref',
            'mobile_number' => 'required|numeric|digits:10|regex:/^[6-9][0-9]{9}$/',
            'mpin' => 'required|numeric|digits:6',
            'amount'        => 'required|numeric|min:0',
            'latitude'  => 'required|numeric|between:-90,90',
            'longitude' => 'required|numeric|between:-180,180',
            'operator' => ['required','integer','in:1,2,3,4,5,6,7,8,9,10',$operatorRule],
        ], [
            'client_ref.required'    => 'Client reference is required.',
            'client_ref.unique'      => 'Client reference must be unique.',
            'client_ref.max'         => 'Client reference must not exceed 20 characters.',
            'mobile_number.required' => 'Mobile number is required.',
            'mobile_number.regex'    => 'Please enter a valid 10-digit Indian mobile number.',
            'amount.required'        => 'Amount is required.',
            'amount.min'             => 'Amount must be greater than or equal to 0.',
            'latitude.required'  => 'Latitude is required.',
            'latitude.numeric'   => 'Latitude must be a number.',
            'latitude.between'   => 'Latitude must be between -90 and 90.',
            'longitude.required' => 'Longitude is required.',
            'longitude.numeric'  => 'Longitude must be a number.',
            'longitude.between'  => 'Longitude must be between -180 and 180.',
            'operator.required' => 'Operator field is required.',
            'operator.integer'  => 'Operator must be a valid number.',
            'operator.between'  => 'Operator must be between 1 and 500.',
            'operator.exists'   => 'Selected operator does not exist or is inactive.',
            'operator.in'   => 'Selected operator is wrong.',
        ]);
    }
      public static function rechargePlan($data)
    {
        $user = Auth::user();
        $operatorRule = Rule::exists('services', 'id')
            ->where(function ($query) use ($user) {
                // Always check global is_active
                $query->where('is_active', 1);
        
                // Role-based check
                if ($user->role === 'api') {
                    $query->where('is_active_api', 1);
                } else {
                    $query->where('is_active_users', 1);
                }
            });
        
        return Validator::make($data, [
            'circle' => 'required|string',
            'operator' => ['required','integer','in:1,2,3,4,5,6,7,8,9,10',$operatorRule],
        ], [
            'operator.required' => 'Operator field is required.',
            'operator.integer'  => 'Operator must be a valid number.',
            'operator.between'  => 'Operator must be between 1 and 500.',
            'operator.exists'   => 'Selected operator does not exist or is inactive.',
            'operator.in'   => 'Selected operator is wrong.',
        ]);
    }
    
}
