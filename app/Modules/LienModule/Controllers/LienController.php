<?php

namespace App\Modules\LienModule\Controllers;

use Laravel\Lumen\Routing\Controller as BaseController;
use Illuminate\Http\Request;
use App\Modules\LienModule\Models\Lien;
use App\Modules\LienModule\Models\LienValidation;
use App\Modules\LienModule\Services\LienService;
use App\Modules\User\Models\User;
use App\Helper\ResponseHelper;
use App\Modules\AuditLog\Services\AuditService;
use App\Modules\Service\Models\Service;
use App\Modules\Commission\Models\CommRule;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Auth;
use App\Modules\Auth\Models\Otp;

class LienController extends BaseController
{
      public function __construct()
    {
        $this->middleware('auth', ['except' => ['']]);
        $this->middleware('check.active', ['except' => '']);

    }
    
     public function addLien(Request $request)
    {
        $validator = LienValidation::validateLien($request->all());
        if ($validator->fails()) {
            return ResponseHelper::error('Validation Failed', $validator->errors(), 422);
        }
         $txnId = $request->given_txn_id;
        $mpin = OTP::verifyMpin($request->mpin);
        if (!$mpin) {
            return ResponseHelper::error("Invalid MPIN", [], 403);
        }
    
        $user = Auth::user();
        
        if (!$user) {
            return ResponseHelper::error('Unauthorized user', [], 401);
        }
        if (!in_array($user->role, ['sadm', 'adm'])) {
            return ResponseHelper::error('Permission denied: Only sadm or adm can add lien', [], 403);
        }
     $tableMap = [
        'PMT'   => 'payout_txns',
        'DMT1'   => 'dmt_txns',
        'DMT2'  => 'dmt_txns',
        'AEPS'  => 'aeps_txns',
        'AEPS2' => 'aeps_txns',
        'BBP'  => 'bbps_txns',
        'RCH'   => 'recharge_txns'
    ];

    $results = [];
    if($txnId){
   foreach ($tableMap as $prefix => $table) {
    if (str_starts_with($txnId, $prefix)) { 
        $data = DB::table($table)
            ->where('txn_id', $txnId)
            ->first(); 

        if ($data) {
            $results = $data;
        }
        break; 
        }
    }

    if (empty($results)) {
        return ResponseHelper::error('Transaction not found', [], 403);
    }}
        DB::beginTransaction();
        try {
            $transfer = LienService::create($user, $request,$results);
            DB::commit();
    
            return ResponseHelper::success('Transaction successful', $transfer, 200);
        } catch (\Exception $e) {
            DB::rollBack();
            return ResponseHelper::error('Transaction Failed', $e->getMessage(), 500);
        }
    }
    
    public function removeLien(Request $request)
    {
        $user = Auth::user();
        if (!$user) {
            return ResponseHelper::error('Unauthorized user', [], 401);
        }
        if (!in_array($user->role, ['sadm', 'adm'])) {
            return ResponseHelper::error('Permission denied: Only sadm or adm can remove lien', [], 403);
        }
        $validator = LienValidation::removeLien($request->all());
        if ($validator->fails()) {
            return ResponseHelper::error('Validation Failed', $validator->errors(), 422);
        }
        $lien = Lien::find($request->id);
        if (!$lien) {
            return ResponseHelper::error('Lien not found', [], 404);
        }

        if ($lien->status === "removed") {
            return ResponseHelper::error('Lien already marked as removed', [], 403);
        }
    
        DB::beginTransaction();
        try {
            $transfer = LienService::removeLien($user, $request, $lien);
            DB::commit();
    
            return ResponseHelper::success('Transaction successful', $transfer, 200);
        } catch (\Exception $e) {
            DB::rollBack();
            return ResponseHelper::error('Transaction Failed', ['error' => $e->getMessage()], 500);
        }
    }
     public function getLien(Request $request)
    {
        $user = Auth::user();
        if (!$user) {
            return ResponseHelper::error('Unauthorized user', [], 401);
        }
        if (in_array($user->role, ['sadm', 'adm'])) {
         
            $liens = Lien::orderBy('created_at', 'desc')->get(); // Latest first
        } else {

            $liens = Lien::where('user_id', $user->id)
                         ->orderBy('created_at', 'desc') // Latest first
                         ->get();
        }
    
        return ResponseHelper::success('Liens fetched successfully', $liens, 200);
    }




  
}
