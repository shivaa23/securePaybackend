<?php

namespace App\Modules\WalletLedger\Models;

use Illuminate\Database\Eloquent\Model;

class WalletLedger2 extends Model 
{

    /**
     * 
     * The attributes that are mass assignable.
     *
     * @var string[]
     */
    protected $fillable = [
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