<?php

namespace App\Modules\WalletLedger\Models;

use Illuminate\Database\Eloquent\Model;

class WalletLedger3 extends Model 
{
  protected $table = 'wallet_ledgers3'; 
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