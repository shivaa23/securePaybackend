<?php

namespace App\Modules\Complains\Controllers;

use Laravel\Lumen\Routing\Controller as BaseController;
use Illuminate\Http\Request;
use App\Modules\Complains\Models\Complains;
use App\Modules\Complains\Models\ComplainsValidation; 
use App\Helper\ResponseHelper;
use App\Modules\Dmt\Models\DmtTxn;
use App\Modules\Recharge\Models\RechargeTxn; 
use App\Modules\Aeps\Models\AepsTxn; 
use App\Modules\Payout\Models\PayoutTxn; 
use App\Modules\Bbps\Models\BbpsTxn;
use App\Modules\User\Models\User;
use Auth;
use Carbon\Carbon;

class ComplainsController extends BaseController
{
    public function __construct()
    {
        $this->middleware('auth', ['except' => []]);
        $this->middleware('check.active', ['except' => []]);
    }

    /**
     * Get complaints for authenticated user
     */
    public function getComplains(Request $request)
    {
        try {
            $user = Auth::user();
    
            if (!$user) {
                return ResponseHelper::error('Unauthenticated', 401);
            }
    
            $query = Complains::query();
        if ($request->has('status') && !empty($request->status) && $request->status !== 'all') {
        $query->where('status', $request->status);
    }

            if (in_array($user->role, ['adm', 'sadm'])) {
                $complains = $query
                    ->orderBy('created_at', 'desc')
                    ->paginate($request->get('per_page', 15));
            } else {
                $complains = $query
                    ->where('user_id', $user->id)
                    ->orderBy('created_at', 'desc')
                    ->paginate($request->get('per_page', 10));
            }
    
            if ($complains->count() > 0) {
                return ResponseHelper::success('Complains fetched successfully', $complains, 200);
            } else {
                return ResponseHelper::success('No complaints found', [], 200);
            }
        } catch (\Exception $e) {
            return ResponseHelper::error('Failed to fetch complaints: ' . $e->getMessage(), 500);
        }
    }

    /**
     * Create a new complaint
     */
    public function createComplains(Request $request)
    {
        try {
            $authUser = Auth::user();
            if (!$authUser) {
                return response()->json([
                    'status' => 'failed',
                    'message' => 'Unauthenticated'
                ], 401);
            }
            $validator = ComplainsValidation::create($request->all());
            if ($validator->fails()) {
                return response()->json([
                    'status' => 'failed',
                    'message' => 'Validation failed',
                    'errors' => $validator->errors()
                ], 422);
            }
            $id = $request->id; 
            $message = $request->message;
            $type = $request->type;
            $serviceModels = [
                'dmt' => DmtTxn::class,
                'recharge' => RechargeTxn::class,
                'payout' => PayoutTxn::class,
                'aeps'=>AepsTxn::class,
                'bbps'=>BbpsTxn::class
            ];
            if (!isset($serviceModels[$type])) {
                return ResponseHelper::error('Invalid service type', 400);
            }
            $model = $serviceModels[$type];
            $txnData = $model::find($id);
            if (!$txnData) {
                return ResponseHelper::error('Transaction not found', 404);
            }
            if (!in_array($authUser->role, ['adm', 'sadm']) && $txnData->user_id != $authUser->id) {
                return ResponseHelper::error('You can only create complaints for your own transactions', 403);
            }
            $txnUser = User::find($txnData->user_id);
            if (!$txnUser) {
                return ResponseHelper::error('Transaction user not found', 404);
            }
            $existingComplaint = Complains::where('txnId', $txnData->txn_id)
                                        ->where('user_id', $txnData->user_id)
                                        ->first();
            if ($existingComplaint) {
                return ResponseHelper::error('Complaint already exists for this transaction', 409);
            }
            $complain = new Complains();
            $complain->user_id = $txnData->user_id;
            $complain->establishment = $txnUser->establishment ?? null;
            $complain->operator = $txnData->operator ?? null;
            $complain->route = $txnData->route ?? null;
            $complain->number = $txnData->sender_mobile ?? $txnData->mobile_number ?? $txnData->number ?? "";
            $complain->amount = $txnData->amount ?? 0;
            $complain->txnId = $txnData->txn_id;
         
            $complain->txn_status = $txnData->txn_status ?? $txnData->status ?? 'PENDING';
            $complain->msg = $message;
            $complain->status = 'open';
            $complain->remark = null;
            $complain->handler = null;
            $complain->txn_date = $txnData->txn_date ?? $txnData->created_at;
            $complain->save();

            return ResponseHelper::success('Complaint created successfully', [
                'complaint_id' => $complain->id,
                'txn_id' => $txnData->txn_id,
                'type' => $type,
                'status' => $complain->status,
                'message' => 'Your complaint has been registered successfully'
            ], 201);

        } catch (\Exception $e) {
            return ResponseHelper::error('Failed to create complaint: ' . $e->getMessage(), 500);
        }
    }

    /**
     * Update complaint status (Admin only)
     */
    public function updateComplains(Request $request)
    {
        try {
            $user = Auth::user();

            if (!$user) {
                return response()->json([
                    'status' => 'failed',
                    'message' => 'Unauthenticated'
                ], 401);
            }

            // Only admin/subadmin allowed
            if (!in_array($user->role, ['adm', 'sadm'])) {
                return response()->json([
                    'status' => 'failed',
                    'message' => 'Unauthorized access'
                ], 403);
            }

            $id = $request->id;
            if (!$id) {
                return response()->json([
                    'status' => 'failed',
                    'message' => 'Complaint ID is required'
                ], 422);
            }

            // Validate request
            $validator = ComplainsValidation::update($request->all());
            if ($validator->fails()) {
                return response()->json([
                    'status' => 'failed',
                    'message' => 'Validation failed',
                    'errors' => $validator->errors()
                ], 422);
            }

            $complaint = Complains::find($id);
            if (!$complaint) {
                return ResponseHelper::error('Complaint not found', 404);
            }

            // Update complaint
            $complaint->remark = $request->remark ?? $complaint->remark;
            $complaint->status = $request->status;
            $complaint->handler = $user->id;
            //$complaint->handled_at = Carbon::now(); // Add timestamp for when it was handled
            $complaint->save();

            // Reload with user relationship for response
            //$complaint->load(['user:id,name,email', 'handlerUser:id,name']);

            return ResponseHelper::success('Complaint updated successfully', [
                'complaint_id' => $complaint->id,
                'txn_id' => $complaint->txnId,
                'status' => $complaint->status,
                'remark' => $complaint->remark,
                'handler' => $complaint->handler,
                'handled_by' => $user->name
            ]);

        } catch (\Exception $e) {
            return ResponseHelper::error('Failed to update complaint: ' . $e->getMessage(), 500);
        }
    }

    /**
     * Get single complaint details
     */
    public function getComplaintDetails($id)
    {
        try {
            $user = Auth::user();

            if (!$user) {
                return response()->json([
                    'status' => 'failed',
                    'message' => 'Unauthenticated'
                ], 401);
            }

            $complaint = Complains::with(['user:id,name,email,establishment']);

            // Regular users can only access their own complaints
            if (!in_array($user->role, ['adm', 'sadm'])) {
                $complaint = $complaint->where('user_id', $user->id);
            }

            $complaint = $complaint->find($id);

            if (!$complaint) {
                return ResponseHelper::error('Complaint not found', 404);
            }

            return ResponseHelper::success('Complaint details fetched successfully', $complaint);

        } catch (\Exception $e) {
            return ResponseHelper::error('Failed to fetch complaint details: ' . $e->getMessage(), 500);
        }
    }
}