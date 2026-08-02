<?php

namespace App\Modules\Recharge\Models;

use Illuminate\Database\Eloquent\Model;

class RechargeTxn extends Model 
{

    /**
     * 
     * The attributes that are mass assignable.
     *
     * @var string[]
     */
    protected $fillable = ['status','api_response','operator_id'
    ];

    /**
     * The attributes excluded from the model's JSON form.
     *
     * @var string[]
     */
    protected $hidden = [  ];
    // In RechargeTxn.php
public function user()
{
    return $this->belongsTo(\App\Modules\User\Models\User::class, 'user_id');
}

    
}