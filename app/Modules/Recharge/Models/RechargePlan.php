<?php

namespace App\Modules\Recharge\Models;

use Illuminate\Database\Eloquent\Model;

class RechargePlan extends Model
{
    protected $table = 'recharge_plans';

    protected $fillable = [
        'name',
        'operator',
        'circle',
        'plan_type',
        'validity_days',
        'price',
        'data_mb',
        'talktime',
        'sms_count',
    ];
}
