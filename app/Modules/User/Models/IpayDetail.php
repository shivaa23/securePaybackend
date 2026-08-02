<?php
namespace App\Modules\User\Models;

use Illuminate\Database\Eloquent\Model;

class IpayDetail extends Model
{
    protected $table = 'ipay_details';

    protected $fillable = [
        'ipay_id',
        'user_id',
        'instid',
        'latitude',
        'longitude',
        "kyc_res"
    ];
      protected $casts = [
        'kyc_res' => 'array',
    ];
}
