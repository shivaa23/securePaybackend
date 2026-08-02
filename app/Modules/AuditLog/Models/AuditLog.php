<?php

namespace App\Modules\AuditLog\Models;

use Illuminate\Database\Eloquent\Model;

class AuditLog extends Model
{
    protected $table = 'audit_logs';
    public $timestamps = false;

    protected $fillable = [
        'user_id', 'role', 'action', 'service_name',
        'request_data', 'response_data', 'ip_address',
        'user_agent', 'status', 'created_at'
    ];
    // In WalletLedger.php
public function user()
{
    return $this->belongsTo(\App\Modules\User\Models\User::class, 'user_id');
}

}