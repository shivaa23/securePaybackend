<?php

namespace App\Modules\Payout\Models;

use Illuminate\Database\Eloquent\Model;

class PayoutBeneficiary extends Model 
{
    protected $table = 'payout_beneficiaries';  

    /**
     * The attributes that are mass assignable.
     *
     * @var string[]
     */
    protected $fillable = [
        'sender_id',
        'beneficiary_name',
        'account_number',
        'ifsc_code',
        'bank_name',
        'type',
        'soliteck_id',
        'api_id',
        'mobile_number',
        'is_verified',
        'is_active',
        'created_at',
        'updated_at'
    ];

    /**
     * The attributes excluded from the model's JSON form.
     *
     * @var string[]
     */
    protected $hidden = [];
}
