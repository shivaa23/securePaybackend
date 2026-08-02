<?php

namespace App\Modules\Dmt\Models;

use Illuminate\Database\Eloquent\Model;

class FinBank extends Model
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
        'operator_id',
        'route',
        'pf',
        'sender_mobile',
        'beneficiary_name',
        'account_number',
        'operator',
        'ifsc_code',
        'bank_name',
        'amount',
        'ccf',
        'gst',
        'comm',
        'charges',
        'di_comm',
        'md_comm',
        'tds',
        'di_tds',
        'md_tds',
        'location',
        'mop',
        'status',
        'api_response',
        'created_at',
        'updated_at',
    ];

    /**
     * The attributes excluded from the model's JSON form.
     *
     * @var string[]
     */
    protected $hidden = [];
}
