<?php
namespace App\Modules\CreditCard\Models;

use Illuminate\Database\Eloquent\Model;
use Validator;
use Illuminate\Validation\Rule;
use Auth;

class CreditCardValidation extends Model
{
    protected $fillable = [];

    /**
     * Validate Pay Bill Request
     */
    public static function payCreditCard($request)
    {
        $user = Auth::user();
    
        $operatorRule = Rule::exists('services', 'id')
            ->where(function ($query) use ($user) {
                $query->where('is_active', 1);
    
                if ($user->role === 'api') {
                    $query->where('is_active_api', 1);
                } else {
                    $query->where('is_active_users', 1);
                }
            });
    
        return Validator::make(
            $request,
            [
                'cnumber'  => 'required|regex:/^[0-9]{15,16}$/u',
                'number'   => 'required|numeric|digits:10',
                'name'     => 'required|string',
                'amount'   => 'required|numeric|between:10,500000',
                'latitude' => 'required|numeric|between:-90,90',
                'longitude'=> 'required|numeric|between:-180,180',
                'mpin'     => 'required|numeric|digits:6',
                 "client_ref"   => 'required|string|unique:credit_cards,client_ref',
                'operator' => ['required','integer',$operatorRule],
            ],
            [
                'number.required'   => 'Mobile number is required.',
                'number.digits'     => 'Please enter a valid 10-digit Indian mobile number.',
    
                'amount.required'   => 'Amount is required.',
                'amount.numeric'    => 'Amount must be a number.',
                'amount.between'    => 'Amount must be between 10 and 500000.',
    
                'latitude.required' => 'Latitude is required.',
                'latitude.numeric'  => 'Latitude must be a number.',
                'latitude.between'  => 'Latitude must be between -90 and 90.',
    
                'longitude.required' => 'Longitude is required.',
                'longitude.numeric'  => 'Longitude must be a number.',
                'longitude.between'  => 'Longitude must be between -180 and 180.',
    
                'mpin.required'     => 'MPIN is required.',
                'mpin.digits'       => 'MPIN must be exactly 6 digits.',
    
                'operator.required' => 'Operator field is required.',
                'operator.integer'  => 'Operator must be a valid number.',
                'operator.exists'   => 'Selected operator does not exist or is inactive.',
            ]
        );
    }

}
