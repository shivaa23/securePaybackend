<?php

namespace App\Modules\Payout\Models;

use Illuminate\Database\Eloquent\Model;

class PayoutSender extends Model 
{

    /**
     * 
     * The attributes that are mass assignable.
     *
     * @var string[]
         */
    protected $fillable = [
        'mobile_number',
        'pipe',
        'sender_name',
        'kyc_status',
        'rem_limit',
        'is_active'
    ];


    /**
     * The attributes excluded from the model's JSON form.
     *
     * @var string[]
     */
    protected $hidden = [  ];
    
public function beneficiary()
{
    return $this->hasMany(\App\Modules\Payout\Models\PayoutBeneficiary::class, 'mobile_number', 'mobile_number');
    
}

    
}