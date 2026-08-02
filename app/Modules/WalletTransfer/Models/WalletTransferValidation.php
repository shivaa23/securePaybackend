<?php

namespace App\Modules\WalletTransfer\Models;

use Validator;
use Illuminate\Validation\Rule;
use Auth;
class WalletTransferValidation
{
    public static function createTransfer($request)
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
        return Validator::make($request, [
            'reciever_id'  => 'required|integer|exists:users,id',
            'amount'      => 'required|numeric|min:1',
            'mpin' => 'required|numeric|digits:6',
            'client_ref'  => 'required|string|unique:wallet_transactions,client_ref|max:100',
            'status'      => 'nullable|in:pending,success,failed',
            'reamrk'=>'sometimes|string',
            // 'operator' => ['required','integer','in:17',$operatorRule],
            
        // ],[  'operator.required' => 'Operator field is required.',
        //     'operator.integer'  => 'Operator must be a valid number.',
        //     'operator.between'  => 'Operator must be between 1 and 500.',
        //     'operator.exists'   => 'Selected operator does not exist or is inactive.',
        //     'operator.in'   => 'Selected operator is wrong.',
        ]);
    }
        public static function createTransferW2W1($request)
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
        return Validator::make($request, [
            'amount'      => 'required|numeric|min:1',
            // 'mpin' => 'required|numeric|digits:6',
            'client_ref'  => 'required|string|unique:wallet_transactions,client_ref|max:100',
            'remark'=>'nullable|string',
            'operator' => ['required','integer','in:79,78',$operatorRule],
        ],[  'operator.required' => 'Operator field is required.',
            'operator.integer'  => 'Operator must be a valid number.',
            'operator.between'  => 'Operator must be between 1 and 500.',
            'operator.exists'   => 'Selected operator does not exist or is inactive.',
            'operator.in'   => 'Selected operator is wrong.',
        ]);
    }
      public static function findUser($request)
    {
        return Validator::make($request, [
            'user_id' => 'required|string',
           
    
        ]);
    }
 public static function createDebit($request)
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
        return Validator::make($request, [
            'reciever_id'  => 'required|integer|exists:users,id',
            'amount'      => 'required|numeric|min:1',
            'mpin' => 'required|numeric|digits:6',
            'client_ref'  => 'required|string|unique:wallet_transactions,client_ref|max:100',
            'status'      => 'nullable|in:pending,success,failed',
            'remark'=>'sometimes|string',
            'operator' => ['required','integer','in:82',$operatorRule],
            
        ],[  'operator.required' => 'Operator field is required.',
            'operator.integer'  => 'Operator must be a valid number.',
            'operator.between'  => 'Operator must be between 1 and 500.',
            'operator.exists'   => 'Selected operator does not exist or is inactive.',
            'operator.in'   => 'Selected operator is wrong.',
        ]);
    }
    public static function updateTransfer($request)
    {
        return Validator::make($request, [
            'id'          => 'required|integer|exists:wallet_transfers,id',
            'amount'      => 'nullable|numeric|min:1',
            'status'      => 'nullable|in:pending,success,failed',
        ]);
    }

    public static function deleteTransfer($request)
    {
        return Validator::make($request, [
            'id' => 'required|integer|exists:wallet_transfers,id'
        ]);
    }
}
