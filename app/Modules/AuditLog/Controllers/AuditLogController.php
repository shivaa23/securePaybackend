<?php

namespace App\Modules\AuditLog\Controllers;

use Laravel\Lumen\Routing\Controller as BaseController;
use App\Traits\ApiResponser;
use Illuminate\Http\Request;
use App\Modules\AuditLog\Models\AuditLog;
use App\Modules\AuditLog\Models\AuditLogValidation;
use Auth;
use App\Helper\ResponseHelper;

class AuditLogController extends BaseController
{
    use ApiResponser;

    public function __construct()
    {
        $this->middleware('auth', ['except' => []]);
        $this->middleware('check.active', ['except' => '']);
        $this->middleware('check.service.time',['except' => '']);
    }

    /**
     * Get all audit logs (paginated).
     */
public function getAuditLogs()
{
    $user = app('auth')->user(); // Lumen Auth
    $role = $user->role;

    $query = AuditLog::with('user')->orderBy('created_at', 'desc');

    if (!in_array($role, ['adm', 'sadm'])) {
        // Normal user: only their own logs
        $query->where('user_id', $user->id);
    }

    $logs = $query->paginate(10);

    // Map only establishment
    $logs->getCollection()->transform(function ($log) {
        $log->establishment = $log->user->establishment ?? 'Unknown';
        unset($log->user); // remove full user object
        return $log;
    });

    if ($logs->count() > 0) {
        return ResponseHelper::success('Audit logs fetched', $logs, 200);
    } else {
        return ResponseHelper::error('No Data Found', [], 200);
    }
}


    /**
     * Get single log by ID.
     */
    public function getAuditLog(Request $request)
    {
        $id = $request->id;
        if (!$id) {
            return ResponseHelper::error('Please Provide Id', '', 422);
        }
    
        // Fetch log with user relation
        $log = AuditLog::with('user')->find($id);
    
        if ($log) {
            // Map only establishment
            $log->establishment = $log->user->establishment ?? 'Unknown';
            unset($log->user); // remove full user object
    
            return ResponseHelper::success('Audit log fetched', $log, 200);
        }
    
        return ResponseHelper::error('Log not found', [], 404);
    }


    /**
     * Delete audit log.
     */
    public function deleteAuditLog(Request $request)
    {
        $id = $request->id;
        $log = AuditLog::find($id);
        if ($log) {
            $log->delete();
            return ResponseHelper::success('Audit log deleted successfully', '', 200);
        }
        return ResponseHelper::error('Log not found', '', 404);
    }
}