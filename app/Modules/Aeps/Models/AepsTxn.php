<?php

namespace App\Modules\Aeps\Models;

use Illuminate\Database\Eloquent\Model;

class AepsTxn extends Model 
{
    /**
     * The attributes that are mass assignable.
     *
     * @var string[]
     */
    protected $fillable = [
        'user_id',
        'txn_id',
        'client_ref',
        'route',
        'pf',
        'aadhaar_number',
        'iin',
        'bank_name',
        'txn_type',
        'amount',
        'comm',
        'balance',
        'status',
        'rrn',
        'api_response',
    ];

    /**
     * The attributes excluded from the model's JSON form.
     *
     * @var string[]
     */
    protected $hidden = [];
    public function user()
{
    return $this->belongsTo(\App\Modules\User\Models\User::class, 'user_id');
}
}
