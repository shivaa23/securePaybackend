<?php

namespace App\Modules\Bbps\Models;

use Illuminate\Database\Eloquent\Model;

class BbpsTxn extends Model 
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
        'biller_id',
        'biller_name',
        'consumer_number',
        'customer_mobile',
        'amount',
        'charges',
        'commission',
        'operator',
        'operator_id',
        'gst',
        'tds',
        'status',
        'rrn',
        'bill_fetch_response',
        'api_response',
    ];

    /**
     * The attributes excluded from the model's JSON form.
     *
     * @var string[]
     */
    protected $hidden = [];
    // In DmtTxn.php model
public function user()
{
    return $this->belongsTo(\App\Modules\User\Models\User::class, 'user_id');
}

}
