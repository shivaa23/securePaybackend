<?php

namespace App\Modules\Risk\Models;

use Illuminate\Database\Eloquent\Model;

class WhiteListedAcc extends Model
{

    protected $table = 'white_listed_accs';
    protected $fillable = [
        'acc_no',
        'acc_name',
        'acc_type',
        'handled_by',
        'limit'
    ];

}
