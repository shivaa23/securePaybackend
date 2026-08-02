<?php

namespace App\Modules\Collection\Controllers;

use Laravel\Lumen\Routing\Controller as BaseController;
use App\Traits\ApiResponser;
use Illuminate\Http\Request;
use App\Modules\Collection\Models\Collection;
use App\Modules\Collection\Models\Collection2;
use App\Modules\Collection\Models\Collection3;
use App\Modules\Account\Models\AccountStatement;
use App\Modules\Bank\Models\BankStatement;
use App\Modules\User\Models\User;
use App\Modules\Account\Models\AccountValidation;
use Auth;
use DB;
use Validator;
use App\Helper\ResponseHelper;
use Illuminate\Support\Facades\Crypt;
use App\Modules\Auth\Models\Otp;
use App\Modules\Commission\Models\CommRule;
use App\Modules\Commission\Models\AcommRule;
use App\Modules\WalletLedger\Services\WalletService2;
use App\Modules\Service\Models\Service;
use Carbon\Carbon;
use App\Modules\Integration\Models\Paypoint;
use App\Modules\Collection\Services\CollectionService;
use App\Modules\AuditLog\Services\AuditService;
use Illuminate\Support\Facades\Log;
class CollectionController extends BaseController
{
    use ApiResponser;

    public function __construct(){
        $this->middleware('auth', ['except' => ['createAccount','updateAccount','encryptPayload','expirePending']]);
        $this->middleware('check.active', ['except' => '']);
        $this->middleware('check.service.time',['except' => '']);
    }  
    public function getCollections(Request $request) {
        $user = Auth::user();
    
        $query = Collection::query();
        if (!in_array($user->role, ['sadm', 'adm'])) {
            $query->where('user_id', $user->id);
        }
    
        if ($request->filled('rrn')) {
            $query->where('rrn', $request->rrn);
        }
          if ($request->filled('amount')) {
            $query->where('amount', $request->amount);
        }
          if ($request->filled('user_id')) {
            $query->where('user_id', $request->user_id);
        }
           if ($request->filled('status')) {
            $query->where('status', $request->status);
        }
        if ($request->filled('payerName')) {
            $query->where('payerName', 'LIKE', '%' . $request->payerName . '%');
        }
          if ($request->filled('payerAddress')) {
            $query->where('payerAddress', 'LIKE', '%' . $request->payerAddress . '%');
        }
           if ($request->filled('from_date') && $request->filled('to_date')) {
             $query->whereDate('created_at', '>=', $request->from_date)
                  ->whereDate('created_at', '<=', $request->to_date);
             }
                            $today = Carbon::now()->format('Y-m-d');
                $totalsQuery = clone $query; // clone base query (with role + filters)
                
                if ($request->filled('from_date') && $request->filled('to_date')) {
                    // Totals for selected date range
                    $totalsQuery->whereDate('created_at', '>=', $request->from_date)
                                ->whereDate('created_at', '<=', $request->to_date);
                } else {
                    // Totals for today only
                    $totalsQuery->whereDate('created_at', $today);
                }
                $unclaimedAmt = (clone $totalsQuery)->where('status', 'Unclaimed')->sum('amount');
                $claimedAmt  = (clone $totalsQuery)->where('status', 'Claimed')->sum('amount');
                $totalAmount = (clone $totalsQuery)->sum('amount');
    
        // $collections = $query->get();
            $collections = $query->latest()->paginate($request->input('paginate', 15));
    
        // return ResponseHelper::success(
        //     'Collections fetched successfully',
        //     $collections,
        //     200
        // );
              return ResponseHelper::success('Transactions fetched', [
                  'total_amount' => $totalAmount,
                'total_claimed_amount' => $claimedAmt,
                'total_unclaimed_amount'  => $unclaimedAmt,
                'data' => $collections
            ], 200);
                //  return ResponseHelper::success('Transactions fetched', $collections, 200);
    }
    public function getCollections2(Request $request){
        $user = Auth::user();
    
        $query = Collection2::query();
    
        if (!in_array($user->role, ['sadm', 'adm'])) {
            $query->where('user_id', $user->id);
        }
    
        if ($request->filled('rrn')) {
            $query->where('rrn', $request->rrn);
        }
    
        if ($request->filled('operator_id')) {
            $query->where('operator_id', $request->operator_id);
        }
    
        if ($request->filled('amount')) {
            $query->where('amount', $request->amount);
        }
    
        if ($request->filled('user_id')) {
            $query->where('user_id', $request->user_id);
        }
    
        if ($request->filled('txn_id')) {
            $query->where('txn_id', $request->txn_id);
        }
    
        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }
    
        if ($request->filled('payerName')) {
            $query->where('payerName', 'LIKE', '%' . $request->payerName . '%');
        }
    
        if ($request->filled('payerAddress')) {
            $query->where('payerAddress', 'LIKE', '%' . $request->payerAddress . '%');
        }
    
        if ($request->filled('from_date') && $request->filled('to_date')) {
            $query->whereDate('created_at', '>=', $request->from_date)
                  ->whereDate('created_at', '<=', $request->to_date);
        }
    
        // ✅ totals logic
        $today = Carbon::now()->format('Y-m-d');
        $totalsQuery = clone $query;
    
        if (!$request->filled('from_date')) {
            $totalsQuery->whereDate('created_at', $today);
        }
    
        $unclaimedAmt = (clone $totalsQuery)->where('status', 'Unclaimed')->sum('amount');
        $claimedAmt   = (clone $totalsQuery)->where('status', 'Claimed')->sum('amount');
        $totalAmount  = (clone $totalsQuery)->sum('amount');
    
        // ✅ export switch
        if ($request->input('export') == 1) {
            $collections = $query->latest()->get();   // no pagination
        } else {
            $collections = $query->latest()->paginate($request->input('paginate', 15));
        }
    
        return ResponseHelper::success('Transactions fetched', [
            'total_amount'           => $totalAmount,
            'total_claimed_amount'   => $claimedAmt,
            'total_unclaimed_amount' => $unclaimedAmt,
            'data' => $collections
        ], 200);
    }
    public function getCollections3(Request $request){
        $user = Auth::user();
    
        $query = Collection3::query();
    
        if (!in_array($user->role, ['sadm', 'adm'])) {
            $query->where('user_id', $user->id);
        }
    
        if ($request->filled('rrn')) {
            $query->where('rrn', $request->rrn);
        }
    
        if ($request->filled('operator_id')) {
            $query->where('operator_id', $request->operator_id);
        }
    
        if ($request->filled('amount')) {
            $query->where('amount', $request->amount);
        }
    
        if ($request->filled('user_id')) {
            $query->where('user_id', $request->user_id);
        }
    
        if ($request->filled('txn_id')) {
            $query->where('txn_id', $request->txn_id);
        }
    
        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }
    
        if ($request->filled('payerName')) {
            $query->where('payerName', 'LIKE', '%' . $request->payerName . '%');
        }
    
        if ($request->filled('payerAddress')) {
            $query->where('payerAddress', 'LIKE', '%' . $request->payerAddress . '%');
        }
    
        if ($request->filled('from_date') && $request->filled('to_date')) {
            $query->whereDate('created_at', '>=', $request->from_date)
                  ->whereDate('created_at', '<=', $request->to_date);
        }
    
        // ✅ totals logic
        $today = Carbon::now()->format('Y-m-d');
        $totalsQuery = clone $query;
    
        if (!$request->filled('from_date')) {
            $totalsQuery->whereDate('created_at', $today);
        }
    
        $unclaimedAmt = (clone $totalsQuery)->where('status', 'Unclaimed')->sum('amount');
        $claimedAmt   = (clone $totalsQuery)->where('status', 'Claimed')->sum('amount');
        $totalAmount  = (clone $totalsQuery)->sum('amount');
    
        // ✅ export switch
        if ($request->input('export') == 1) {
            $collections = $query->latest()->get();   // no pagination
        } else {
            $collections = $query->latest()->paginate($request->input('paginate', 15));
        }
    
        return ResponseHelper::success('Transactions fetched', [
            'total_amount'           => $totalAmount,
            'total_claimed_amount'   => $claimedAmt,
            'total_unclaimed_amount' => $unclaimedAmt,
            'data' => $collections
        ], 200);
    }
    // public function getCollections2(Request $request){
    //     $user = Auth::user();
    
    //     $query = Collection2::query();
    //     if (!in_array($user->role, ['sadm', 'adm'])) {
    //         $query->where('user_id', $user->id);
    //     }
    
    //     if ($request->filled('rrn')) {
    //         $query->where('rrn', $request->rrn);
    //     }
    //       if ($request->filled('operator_id')) {
    //         $query->where('operator_id', $request->operator_id);
    //     }
    //       if ($request->filled('amount')) {
    //         $query->where('amount', $request->amount);
    //     }
    //       if ($request->filled('user_id')) {
    //         $query->where('user_id', $request->user_id);
    //     }
    //       if ($request->filled('txn_id')) {
    //         $query->where('txn_id', $request->txn_id);
    //     }
    //       if ($request->filled('status')) {
    //         $query->where('status', $request->status);
    //     }
    //     if ($request->filled('payerName')) {
    //         $query->where('payerName', 'LIKE', '%' . $request->payerName . '%');
    //     }
    //       if ($request->filled('payerAddress')) {
    //         $query->where('payerAddress', 'LIKE', '%' . $request->payerAddress . '%');
    //     }
    //       if ($request->filled('from_date') && $request->filled('to_date')) {
    //          $query->whereDate('created_at', '>=', $request->from_date)
    //               ->whereDate('created_at', '<=', $request->to_date);
    //          }
    //                         $today = Carbon::now()->format('Y-m-d');
    //             $totalsQuery = clone $query; // clone base query (with role + filters)
                
    //             if ($request->filled('from_date') && $request->filled('to_date')) {
    //                 // Totals for selected date range
    //                 $totalsQuery->whereDate('created_at', '>=', $request->from_date)
    //                             ->whereDate('created_at', '<=', $request->to_date);
    //             } else {
    //                 // Totals for today only
    //                 $totalsQuery->whereDate('created_at', $today);
    //             }
    //             $unclaimedAmt = (clone $totalsQuery)->where('status', 'Unclaimed')->sum('amount');
    //             $claimedAmt  = (clone $totalsQuery)->where('status', 'Claimed')->sum('amount');
    //             $totalAmount = (clone $totalsQuery)->sum('amount');
    
    //     // $collections = $query->get();
    //         $collections = $query->latest()->paginate($request->input('paginate', 15));
    
    //     // return ResponseHelper::success(
    //     //     'Collections fetched successfully',
    //     //     $collections,
    //     //     200
    //     // );
    //           return ResponseHelper::success('Transactions fetched', [
    //               'total_amount' => $totalAmount,
    //             'total_claimed_amount' => $claimedAmt,
    //             'total_unclaimed_amount'  => $unclaimedAmt,
    //             'data' => $collections
    //         ], 200);
    //             //  return ResponseHelper::success('Transactions fetched', $collections, 200);
    // }
    public function claim(Request $request) {
        $user = Auth::user();
    
        // Validate only ID
        $validator = Validator::make($request->all(), [
            'id' => 'required|integer|exists:collections,id',
             'remark' => 'sometimes|string',
        ]);
    
        if ($validator->fails()) {
            return ResponseHelper::error($validator->errors(), 'Validation error', 422);
        }
    
        // Fetch collection
        $collections = Collection::find($request->id);
        if (!$collections) {
            return ResponseHelper::error("Collection not found", [], 404);
        }
    
        // Fix incorrect status condition
        if ($collections->status !== "Unclaimed") {
            return ResponseHelper::error("Collection status is already {$collections->status}", [], 403);
        }
    
        // Fetch service
        $service = Service::find(92);
        if (!$service) {
            return ResponseHelper::error("Service not found", [], 404);
        }
          if ($service->is_active==0) {
            return ResponseHelper::error("Service  is not active", [], 403);
        }
        // Commission check
        $comm = CommRule::calculateCommission($user, $service->name, $collections->amount);
        if (!$comm) {
            return ResponseHelper::error('Commission Slab Not Configured', [], 403);
        }
    
        $a_comm = AcommRule::calculateAdminCommission(
            $service->name,
            $collections->amount,
            $service->route
        );
        if (!$a_comm) {
            return ResponseHelper::error('Admin Commission Slab Not Configured', [], 403);
        }
    
        // Final commission calculation
        $comm = CommRule::finalCommission($comm, $collections->amount, $service);
        $a_comm = AcommRule::finalCommission($a_comm, $collections->amount);
    
        DB::beginTransaction();
        try {
    
            WalletService2::claim($user, $collections, $service);
    
            $collections->status = "Claimed";
              $collections->remark = $request->remark;
            $collections->save();
    
            DB::commit();
    
            return ResponseHelper::success("Claim processed successfully", [], 200);
    
        } catch (\Exception $e) {
    
            DB::rollBack();
            return ResponseHelper::error('Claim Failed', $e->getMessage(), 500);
        }
    }
    public function claim2(Request $request) {
        $user = Auth::user();
    
        // Validate only ID
        $validator = Validator::make($request->all(), [
            'id' => 'required|integer|exists:collections2,id',
             'remark' => 'sometimes|string',
        ]);
    
        if ($validator->fails()) {
            return ResponseHelper::error($validator->errors(), 'Validation error', 422);
        }
    
        // Fetch collection
        $collections = Collection2::find($request->id);
       $service = Service::find(366);
        if (!$service) {
            return ResponseHelper::error("Service not found", [], 404);
        }
          if ($service->is_active==0) {
            return ResponseHelper::error("Service  is not active", [], 403);
        }
        if (!$collections) {
            return ResponseHelper::error("Collection not found", [], 404);
        }
    
        if ($collections->status != "UNCLAIMED") {
            return ResponseHelper::error("Collection status is already {$collections->status}", [], 403);
        }

      
    
        DB::beginTransaction();
        try {
    
            WalletService2::claim2($user, $collections, $service);
    
            $collections->status = "Claimed";
              $collections->remark = $request->remark;
            $collections->save();
    
            DB::commit();
    
            return ResponseHelper::success("Claim processed successfully", [], 200);
    
        } catch (\Exception $e) {
    
            DB::rollBack();
            return ResponseHelper::error('Claim Failed', $e->getMessage(), 500);
        }
    }
    public function claim3(Request $request) {
        $user = Auth::user();
    
        // Validate only ID
        $validator = Validator::make($request->all(), [
            'id' => 'required|integer|exists:collections3,id',
             'remark' => 'sometimes|string',
        ]);
    
        if ($validator->fails()) {
            return ResponseHelper::error($validator->errors(), 'Validation error', 422);
        }
    
        // Fetch collection
        $collections = Collection3::find($request->id);
       $service = Service::find(367);
        if (!$service) {
            return ResponseHelper::error("Service not found", [], 404);
        }
          if ($service->is_active==0) {
            return ResponseHelper::error("Service  is not active", [], 403);
        }
        if (!$collections) {
            return ResponseHelper::error("Collection not found", [], 404);
        }
    
        if ($collections->status != "UNCLAIMED") {
            return ResponseHelper::error("Collection status is already {$collections->status}", [], 403);
        }

      
    
        DB::beginTransaction();
        try {
    
            WalletService2::claim2($user, $collections, $service);
    
            $collections->status = "Claimed";
              $collections->remark = $request->remark;
            $collections->save();
    
            DB::commit();
    
            return ResponseHelper::success("Claim processed successfully", [], 200);
    
        } catch (\Exception $e) {
    
            DB::rollBack();
            return ResponseHelper::error('Claim Failed', $e->getMessage(), 500);
        }
    }
    public function expirePending(Request $request){
        $time = Carbon::now()->subMinutes(15);
    
        Log::info('ExpirePending cron started', [
            'check_time' => $time->toDateTimeString()
        ]);
    
        $updatedCount = Collection3::where('status', 'PENDING')
            ->where('created_at', '<=', $time)
            ->update(['status' => 'FAILED']);
    
        Log::info('ExpirePending cron completed', [
            'updated_count' => $updatedCount
        ]);
    
        return response()->json([
            'status' => 'SUCCESS',
            'message' => 'Expired pending transactions updated successfully',
            'updated_count' => $updatedCount
        ]);
    }
    public function createAccount(Request $request){ 
        if (!in_array(Auth::user()->role, ['sadm', 'adm'])) {
            return ResponseHelper::error('Unauthorised Action', [], 403);
        }
       	$validator = AccountValidation::createAccount($request->all());
        if ($validator->fails()) {
            return ResponseHelper::error($validator->errors(),'Validation Failed',  422);
        }
        $user=User::find($request->user_id);
        $account=new Account();
        $accountData=  $account->where("user_id",$request->user_id)->first();
        if ($accountData) {
            return ResponseHelper::error('Account already exists', [], 403);
        }

        // $account->account=$request->account;
        $account->name          = $request->name ??$user->name;
        $account->user_id       = $request->user_id;
        $account->establishment = $request->establishment ??$user->establishment;
        $account->mobile        = $request->mobile ??$user->mobile;
        $account->asm    = $user?$user->parent:null;
        $account->credit_limit  = $request->credit_limit ;
        $account->balance       = $request->balance ??0;
  
        
        if($account->save()){
            return ResponseHelper::success('Account Created Successfully', $account, 200);
        }else{
            return ResponseHelper::error('Something Went Wrong', '', 403);

        }
    }
    public function updateAccount(Request $request){
       	$validator = AccountValidation::updateAccount($request->all());
        if ($validator->fails()) {
            return ResponseHelper::error( $validator->errors(),'Validation Error', 422);
        }
        if (!in_array(Auth::user()->role, ['sadm', 'adm',])) {
            return ResponseHelper::error('Unauthorised Action', [], 403);
        }
        $account=Account::find($request->id);
        if (!$account) {
            return ResponseHelper::error('Account not found', '', 403);
        }
        if($account){
            $account->name          = $request->name ?? $account->name;
            $account->user_id       = $request->user_id ?? $account->user_id;
            $account->establishment = $request->establishment ?? $account->establishment;
            $account->mobile        = $request->mobile ?? $account->mobile;
            //$account->type        = $request->type ?? $account->type;
            $account->asm           = $request->asm ?? $account->asm;
            $account->credit_limit  = $request->credit_limit ?? $account->credit_limit;
            $account->balance       = $request->balance ?? $account->balance;
            $account->status        = $request->status??$account->status;
            if($account->save()){
                return ResponseHelper::success('Account updated Successfully',$account, 200);
            }
        }else{
            return ResponseHelper::error('Account not found', '', 403);
        }
    }
    public function getAccountSchema(){
        $schema = AccountValidation::getAccountSchema();
        return  ResponseHelper::success('Schema fetchSuccessfully',$schema, 200);;
    }
    public function deleteAccount(Request $request){
        $validator = AccountValidation::deleteAccount($request->all());
        if ($validator->fails()) {
            return ResponseHelper::error($validator->errors(),'Validation Failed',  422);
        }
    
        $account = Account::find($request->id);
        if ($account->delete()) {
            return ResponseHelper::success( 'Account deleted successfully', [], 200);
        }
        return ResponseHelper::error('Account could not be deleted', [], 403);
    }
    public function getAccounOthertSchema(){
        $schema = AccountValidation::getAccountOthersSchema();
        return  ResponseHelper::success('Schema fetchSuccessfully',$schema, 200);;
    }
    public function deleteLastTxn(Request $request){
        $rules = ['account_id' => 'required'];
        $validator = Validator::make($request->all(), $rules);
        if ($validator->fails()) {
            return ResponseHelper::error($validator->errors(),'Validation Error', 403);
        }
        if (Auth::user()->role != 'sadm') {
            return ResponseHelper::error('Unauthorised Access', '', 403);
        }
        $txna = AccountStatement::where('account_id', $request->account_id)
                    ->latest()
                    ->first();
        if (!$txna) {
            return ResponseHelper::error('No Transaction Found To Delete', '', 403);
        }
        try {
            DB::beginTransaction();
            $credit = $txna->credit ?? 0;
            $debit  = $txna->debit ?? 0;
            $user = Account::find($txna->account_id);
            if (!$user) {
                DB::rollBack();
                return ResponseHelper::error('Account not found', '', 404);
            }
            // Adjust balance
            $user->increment('balance', $credit);
            $user->decrement('balance', $debit);
            // Update bank transaction if applicable
            $bnkTxn = BankStatement::find($txna->bank_txnid);
            if ($bnkTxn && $bnkTxn->status == 1) {
                $bnkTxn->status = 0;
                $bnkTxn->save();
            }
            // Delete ledger entry
            $txna->delete();
            if ($txna) {
                DB::commit();
                return ResponseHelper::success('Transaction deleted successfully', '', 200);
            } else {
                DB::rollBack();
                return ResponseHelper::error('Request cannot be processed this time', '', 403);
            }
        } catch (\Exception $e) {
            DB::rollBack();
            return ResponseHelper::error('Something went wrong: ' . $e->getMessage(), '', 500);
        }
    }
    public function checkTxnStatus(Request $request) {
        $validator = Validator::make($request->all(), [
            'txn_id' => 'required|exists:collections3,txn_id',
        ]);
    
        if ($validator->fails()) {
            return ResponseHelper::error($validator->errors(), 'Validation error', 422);
        }
    
        $txn = Collection3::where('txn_id', $request->txn_id)->first();
    
        if (!$txn) {
            return ResponseHelper::error( 'Transaction not found',[],404);
        }
    
        if ($txn->status === 'CLAIMED') {
            return ResponseHelper::success("Transaction successful",$txn,200);
        }
    
        return ResponseHelper::success('Transaction is not claimed yet',$txn,201);
    }
    public function markUnclaimed2(Request $request){
        $user = Auth::user();
    
        if (!in_array($user->role, ['sadm','adm'])) {
            return ResponseHelper::error('Unauthorised Action', [], 403);
        }
    
      
        $validator = Validator::make($request->all(), [
            'id'          => 'required|integer|exists:collections2,id',
            'status'      => 'required|string',
            'operator_id' => 'sometimes|string',   
            'remark'      => 'sometimes|string'
        ]);
    
        if ($validator->fails()) {
            return ResponseHelper::error($validator->errors(), 'Validation error', 422);
        }
    
        $collection = Collection2::find($request->id);
    
        if (!$collection) {
            return ResponseHelper::error('Collection not found', [], 404);
        }
    
    

        $allowedStatuses = ['UNCLAIMED', 'FAILED'];
        $newStatus = strtoupper($request->status);
    
        if (!in_array($newStatus, $allowedStatuses)) {
            return ResponseHelper::error(
                'Invalid status. Allowed: UNCLAIMED, FAILED',
                [],
                422
            );
        }
    
        DB::beginTransaction();
        try {
    
            $collection->status = $newStatus;
    
         
            $collection->operator_id = $request->operator_id;
    
            if ($request->remark) {
                $collection->remark = $request->remark;
            }
    
            $collection->save();
    
            DB::commit();
    
            return ResponseHelper::success(
                'Status updated successfully',
                $collection,
                200
            );
    
        } catch (\Exception $e) {
            DB::rollBack();
            return ResponseHelper::error('Update Failed', $e->getMessage(), 500);
        }
    }
    public function initiateDynamicQrCollection(Request $request)
    {
        $user = Auth::user();
        if (!$user) {
            return ResponseHelper::error('Unauthenticated', '', 401);
        }
        $validator = Validator::make($request->all(), [
            'amount'    => 'required|numeric|min:1',
            'latitude'  => 'required',
            'customer_mobile'  => 'required',
            'longitude' => 'required',
        ]);
    
        if ($validator->fails()) {
            return ResponseHelper::error('Validation failed', $validator->errors(), 422);
        }
        if (!in_array($user->role, ["api", "dd", "ret"])) {
            return ResponseHelper::error('Action not Authorised', '', 403);
        }
        $service = Service::find(367);
        if (!$service) {
            return ResponseHelper::error('Service not Configured', '', 403);
        }
        if ($service->is_active==0) {
            return ResponseHelper::error("Service  is not active", [], 403);
        }
        $comm = CommRule::calculateCommission($user, $service->name, $request->amount);
        if (!$comm) {
            return ResponseHelper::error('Commission Slab Not Configured', '', 403);
        }
    
        $a_comm = AcommRule::calculateAdminCommission($service->name, $request->amount, $service->route);
        if (!$a_comm) {
            return ResponseHelper::error('Admin Commission Slab Not Configured', '', 403);
        }
    
        $comm   = CommRule::finalCommission($comm, $request->amount, $service);
        $a_comm = AcommRule::finalCommission($a_comm, $request->amount);
        DB::beginTransaction();
    
        try {
    
            $qrTxn = CollectionService::createCollection($user, $service, $request, $comm, $a_comm);
    
            if (!$qrTxn) {
                throw new \Exception("Transaction creation failed");
            }
    
            $auditLog = AuditService::logRequest($user, $request, $service);
    
            DB::commit();
    
        } catch (\Exception $e) {
            DB::rollBack();
            \Log::error("QR Init Failed: " . $e->getMessage());
            return ResponseHelper::error('Transaction Init Failed', $e->getMessage(), 500);
        }
        $payload = [
            "OrderId"            => $qrTxn->txn_id,
            "Remarks"            => "QR Payment",
            "CollectExpiryAfter" => 5,
            "Amount"             => $request->amount,
            "Latitude"           => $request->latitude,
            "Longitude"          => $request->longitude,
            "Location"           => $request->pincode ?? "000000",
            "IPAddress"          => $request->ip(),
            "DeviceSerial"       => $request->header('User-Agent'),
            "DeviceOS"           => PHP_OS,
            "AppTechName"        => "com.digikhata.in"
        ];
    
        $data = [
            'url'     => 'https://walletupi.paypointz.com/upi/generatedynamicqr',
            'payload' => json_encode($payload),
            'method'  => 'POST'
        ];
        $response = Paypoint::serviceQr($data);
        $response = json_decode($response, true);
        DB::beginTransaction();
    
        try {
    
            AuditService::updateResponse($auditLog, $response);
            if (!$response) {
                $qrTxn->update([
                    'status'       => 'FAILED',
                    'api_response' => json_encode($response)
                ]);
    
                DB::commit();
                return ResponseHelper::error('Invalid API Response', $response, 500);
            }
    
            $resultCode   = $response['resultCode'] ?? null;
            $resultStatus = strtolower($response['resultStatus'] ?? '');
            $resultMsg    = $response['resultMessage'] ?? 'Transaction Failed';
            if ($resultCode === "000" && $resultStatus === "success") {
    
                $qrImage = $response['data']['qrCodeImage'] ?? null;
    
                DB::commit();
    
                return ResponseHelper::success('QR Generated Successfully', [
                    'txn_id'    => $qrTxn->txn_id,
                    'order_id'  => $qrTxn->txn_id,
                    'amount'    => $request->amount,
                    'qr_base64' => $qrImage,
                    'message'   => $resultMsg
                ], 200);
            }
            $qrTxn->update([
                'status'       => 'FAILED',
                'api_response' => json_encode($response)
            ]);
    
            DB::commit();
    
            switch ($resultCode) {
                case "401":
                    return ResponseHelper::error('Access Denied', $response, 401);
    
                case "RNF":
                    return ResponseHelper::error('No merchant virtual id found.', $response, 400);
    
                case "001":
                    return ResponseHelper::error('Unable to generate QR code.', $response, 400);
    
                case "500":
                    return ResponseHelper::error('Server error. Please try later.', $response, 500);
    
                default:
                    return ResponseHelper::error($resultMsg, $response, 400);
            }
    
        } catch (\Exception $e) {
    
            DB::rollBack();
            \Log::error("QR Update Failed: " . $e->getMessage());
    
            return ResponseHelper::error('Transaction Update Failed', $e->getMessage(), 500);
        }
    }

}
