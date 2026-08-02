<?php
namespace App\Modules\User\Models;

use Illuminate\Database\Eloquent\Model;

class FpayDetail extends Model
{
    protected $table = 'fpay_details';

    protected $fillable = [
        'fpay_id',
        'user_id',
        'fingId',
        'latitude',
        'longitude',
        "kyc_res"
    ];
      protected $casts = [
        'kyc_res' => 'array',
    ];
}
