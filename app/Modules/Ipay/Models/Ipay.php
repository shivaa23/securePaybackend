<?php

namespace App\Modules\Ipay\Models;

use Illuminate\Database\Eloquent\Model;

class Ipay extends Model
{

    protected $table = 'ipay_details';

    protected $fillable = [
       'latitude',
        'longitude',
        'instid',
        
        'ipay_id'
        
    ];

    protected $hidden = [];
}
