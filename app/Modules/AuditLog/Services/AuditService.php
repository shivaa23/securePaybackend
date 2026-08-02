<?php
namespace App\Modules\AuditLog\Services;

use App\Modules\AuditLog\Models\AuditLog;

class AuditService
{
    public static function logRequest($user, $request, $service)
    {
        
        $auditLog = new AuditLog;
        $auditLog->user_id=$user->id;
        $auditLog->role=$user->role;
        $auditLog->action=$service->name;
        $auditLog->service_name=$service->name;
        $auditLog->request_data=json_encode($request->all());
        $auditLog->ip_address=$request->ip();
        $auditLog->client_ref=$request->client_ref;
        
        $auditLog->user_agent=$request->header('User-Agent');
        $auditLog->status='INITIATED';
        $auditLog->save();
        return $auditLog;
    }

    public static function updateResponse($auditLog, $response)
    {
        $auditLog->update([
            'response_data' => json_encode($response),
            'status'        => 'SUCCESS',
        ]);
    }
}