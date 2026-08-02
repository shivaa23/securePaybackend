<?php

namespace App\Modules\Risk\Models;

use Illuminate\Database\Eloquent\Model;

class BlackListedAcc extends Model
{
    protected $table = 'black_listed_accs';
    protected $fillable = [
        'acc_no',
        'handled_by'
    ];

}
