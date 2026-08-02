<?php

namespace App\Modules\Auth\Models;

use Illuminate\Database\Eloquent\Model;

class QrSession extends Model
{
    protected $fillable = [
        'token', 'user_id', 'status','expires_at'
    ];
}