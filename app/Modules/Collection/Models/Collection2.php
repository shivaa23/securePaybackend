<?php

namespace App\Modules\Collection\Models;

use Illuminate\Database\Eloquent\Model;

class Collection2 extends Model 
{
    /**
     * Define table name
     *
     * @var string
     */
    protected $table = 'collections2';
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
         'qr_string',
        'api_response',
    ];

    protected $hidden = [  
        // hidden fields
    ];
}
