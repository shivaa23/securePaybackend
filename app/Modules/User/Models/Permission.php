<?php

namespace App\Modules\User\Models;

use Illuminate\Database\Eloquent\Model;

class Permission extends Model
{
    protected $table = 'permissions';

    protected $fillable = [
        'dmt1',
        'super',
        'vendor',
        'upi',
        'utkal_upi',
        'recharge',
        'aeps1',
        'aeps2',
        'bbps',
        'credit_card',
        'travel',
        'electricity',
        'fund_req',
        'fund_req_actions',
        'txn_actions',
        'user_actions',
        'banking',
        'account',
        'bank',
        'unclaimed',
        'settings',
        'add_lien',
        'remove_lien',
        'bbps_offline',
        'user_id',
        'users',
        'txn',
        'w_txn',
        'soliteck',
        'abhayam',
        'utkal',
        'qr1',
        'qr2',
        'qr3',
        'claimqr2'
    ];

    public $timestamps = false;
}
