<?php

namespace App\Modules\Dmt\Models;

use Illuminate\Database\Eloquent\Model;

class DmtBeneficiarie extends Model 
{

    /**
     * 
     * The attributes that are mass assignable.
     *
     * @var string[]
     */
     
    protected $fillable = ['pipe','sender','beneficiary_name','bene_id','account_number','ifsc_code','bank_name','mobile_number','is_verified'
    ];

    /**
     * The attributes excluded from the model's JSON form.
     *
     * @var string[]
     */
    protected $hidden = [  ];
    
}