<?php

namespace App\Modules\AuditLog\Models;

use Illuminate\Support\Facades\Validator;

class AuditLogValidation
{
    public static function createLog($data)
    {
        return Validator::make($data, [
            'user_id'      => 'required|integer',
            'role'         => 'required|string|max:30',
            'action'       => 'required|string|max:255',
            'service_name' => 'nullable|string|max:100',
            'request_data' => 'nullable|string',
            'response_data'=> 'nullable|string',
            'status'       => 'nullable|in:success,failed'
        ]);
    }

    public static function updateLog($data)
    {
        return Validator::make($data, [
            'action'       => 'nullable|string|max:255',
            'service_name' => 'nullable|string|max:100',
            'response_data'=> 'nullable|string',
            'status'       => 'nullable|in:success,failed'
        ]);
    }
}