<?php

namespace App\Modules\Bank\Controllers;

use Laravel\Lumen\Routing\Controller as BaseController;
use App\Modules\Bank\Models\BankStatement;
use App\Modules\Bank\Models\Bank;
use App\Modules\Bank\Models\BankStatementValidation;
use App\Traits\ApiResponser;
use Illuminate\Http\Request;
use Auth;
use App\Helper\ResponseHelper;
use Illuminate\Support\Facades\DB;


class BankStatementController extends BaseController
{
    use ApiResponser;
    
    public function __construct()
    {
        $this->middleware('auth', ['except' => ['']]);
        $this->middleware('check.active', ['except' => ['']]);
        $this->middleware('check.service.time',['except' => '']);
    } 
    public function getBankStatement(Request $request)
    {   
         $validator = BankStatementValidation::getBankStatement($request->all());
        if ($validator->fails()) {
            return ResponseHelper::error("Validation error", $validator->errors(), 422);
        }
        $statements = BankStatement::where("bank_id",$request->bank_id)->orderBy('id', 'desc')->get(); 
        return ResponseHelper::success('Bank statements fetched', $statements, 200);
    }
     public function getUnclaimedEntries(Request $request)
    {
        $user = $request->user();

        if (!in_array($user->role, ['adm', 'sadm'])) {
            return ResponseHelper::error(
                'You are not authorized to access this data',
                [],
                401
            );
        }
        $statements = BankStatement::where('status', 0)
            ->orderBy('id', 'desc')
            ->get();
    
        return ResponseHelper::success(
            'Unclaimed data fetched successfully',
            $statements,
            200
        );
    }


    public function getBankStatementSchema()
    {
        $schema = BankStatementValidation::bankSchema();
        return ResponseHelper::success('Bank statement schema fetched', $schema, 200);
    }
   public function createBankStatement(Request $request)
    {
        $user = Auth::user();
        
        // Validate request
        $validator = BankStatementValidation::createBankStatement($request->all());
        if ($validator->fails()) {
            return ResponseHelper::error("Validation error", $validator->errors(), 422);
        }
    
        // Authorization
        if (!in_array($user->role, ['adm', 'sadm'])) {
            return ResponseHelper::error('Operation Not Authorised', [], 403);
        }
    
        // Fetch the bank
        $bank = Bank::find($request->bank_id);
        if (!$bank) {
            return ResponseHelper::error('Bank not found', [], 404);
        }
    
        // Create bank statement
        $statement = new BankStatement();
        $statement->bank_id     = $request->bank_id;
        $statement->bank_name     = $bank->bank_name;
        $statement->particulars = $request->particulars;
        $statement->handle_by   = $user->name;
        $statement->remark      = $request->remark;
        $statement->credit      = $request->credit ?? 0;
        $statement->debit       = $request->debit ?? 0;
        $statement->mop         = $request->mop;
        $statement->date        = $request->date;
    
        if ($request->credit > 0) {
            $bank->balance += $request->credit;
             $statement->balance     =  $bank->balance;
        } elseif ($request->debit > 0) {
          $bank->balance -= $request->debit;
           $statement->balance  =  $bank->balance;
        }
    
        // Save everything inside a transaction
        DB::beginTransaction();
        try {
              $bank->save();
            $statement->save();
          
            DB::commit();
            return ResponseHelper::success('Bank statement created successfully', $statement, 200);
        } catch (\Exception $e) {
            DB::rollBack();
            return ResponseHelper::error('Bank statement cannot be created', ['error' => $e->getMessage()], 500);
        }
    }

    public function updateBankStatement(Request $request)
    {
        $validator = BankStatementValidation::updateBankStatement($request->all());
        if ($validator->fails()) {
            return ResponseHelper::error('Validation failed', $validator->errors(), 422);
        }

        if (Auth::user()->role != "sadm") {
            return ResponseHelper::error('Operation Not Authorised', '', 403);
        }

        $statement = BankStatement::find($request->id);
        if (!$statement) {
            return ResponseHelper::error('Bank statement not found', '', 404);
        }

        $statement->bank_id     = $request->bank_id     ?? $statement->bank_id;
        $statement->particulars = $request->particulars ?? $statement->particulars;
        $statement->claimed_by  = $request->claimed_by  ?? $statement->claimed_by;
        $statement->credit      = $request->credit      ?? $statement->credit;
        $statement->debit       = $request->debit       ?? $statement->debit;
        $statement->balance     = $request->balance     ?? $statement->balance;
        $statement->status      = $request->status      ?? $statement->status;
        $statement->date        = $request->date        ?? $statement->date;

        if ($statement->save()) {
            return ResponseHelper::success('Bank statement updated successfully', $statement, 200);
        } else {
            return ResponseHelper::error('Bank statement cannot be updated', '', 403);
        }
    }
    public function deleteBankStatement(Request $request)
    {
        $validator = BankStatementValidation::deleteBankStatement($request->all());
        if ($validator->fails()) {
            return ResponseHelper::error('Failed', $validator->errors(), 422);
        }
        if (Auth::user()->role != "sadm") {
            return ResponseHelper::error('Operation Not Authorised', '', 403);
        }

        $statement = BankStatement::find($request->id);
        if (!$statement) {
            return ResponseHelper::error('Bank statement not found', '', 404);
        }
        if ($statement->balance == 0) {
            $statement->delete();
            return ResponseHelper::success('Bank statement deleted successfully', '', 200);
        } else {
            return ResponseHelper::error('Bank statement cannot be deleted while balance is not zero', '', 403);
        }
    }
}
