<?php

namespace App\Modules\Account\Controllers;

use Laravel\Lumen\Routing\Controller as BaseController;
use App\Traits\ApiResponser;
use Illuminate\Http\Request;
use App\Modules\Account\Models\AccountStatement;
use App\Modules\Account\Models\Account;
use App\Modules\User\Models\User;
use App\Modules\Account\Models\AccountStatementValidation;
use App\Modules\Bank\Models\BankStatement;
use Auth;
use App\Helper\ResponseHelper;
use Illuminate\Support\Facades\DB;
class AccountStatementController extends BaseController
{
    use ApiResponser;

    public function __construct()
    {
        $this->middleware('auth', ['except' => ['encryptPayload']]);
        $this->middleware('check.active', ['except' => '']);
        $this->middleware('check.service.time',['except' => '']);
    }


    public function getAccountStatement(Request $request)
    {
        if (!in_array(Auth::user()->role, ['sadm', 'adm'])) {
            return ResponseHelper::error('Unauthorised Action', [], 403);
        }
         $validator = AccountStatementValidation::getAccountStatement($request->all());
        if ($validator->fails()) {
            return ResponseHelper::error('Validation Failed', $validator->errors(), 422);
        }

   $statements = AccountStatement::where('account_id', $request->account_id)->orderBy('id', 'desc') ->paginate(10);


        return ResponseHelper::success('Account statements fetched successfully', $statements, 200);
    }

     public function createAccountStatement(Request $request)
    {
        $user = Auth::user();
    
        $validator = AccountStatementValidation::createAccountStatement($request->all());
        if ($validator->fails()) {
            return ResponseHelper::error('Validation Failed', $validator->errors(), 422);
        }
    
        if (!in_array($user->role, ['sadm', 'adm'])) {
            return ResponseHelper::error('Unauthorised Action', [], 403);
        }
    
        // if (!$bankStatement) {
        //     return ResponseHelper::error('Bank statement not found', [], 404);
        // }
    
        $account = Account::find($request->account_id);
         
        if (!$account) {
            return ResponseHelper::error('Account not found', [], 404);
        }
        $userData=User::find($account->user_id);
    
        try {
            DB::beginTransaction();
    
            $credit = $request->credit ?? 0.00;
            $debit  = $request->debit ?? 0.00;
    
            // Update account balance
            if ($credit > 0) {
                $account->balance += $credit;
            } elseif ($debit > 0) {
                if ($account->balance < $debit) {
                    return ResponseHelper::error('Insufficient balance', [], 400);
                }
                $account->balance -= $debit;
            }
    
            $account->save();
    
            $statement = new AccountStatement();
            $statement->account_id   = $request->account_id;
            $statement->remarks      = $userData->establishment;
            $statement->bank_id      = $request->bank_id;
            $statement->claimed_by   = $user->id;
            $statement->credit       = $credit;
            $statement->debit        = $debit;
            $statement->balance      = $account->balance; // updated balance
            $statement->particulars  = $request->particulars;
            $statement->bank_txnid  = $request->id;
    
            $statement->save();
            $bankStatement = BankStatement::find($request->id);
            if($bankStatement){
                $bankStatement->status = 1;
                $bankStatement->save();
            }
            
            DB::commit();
    
            return ResponseHelper::success('Account statement created successfully', $statement, 200);
        } catch (\Exception $e) {
            DB::rollBack();
            return ResponseHelper::error('Something went wrong', $e->getMessage(), 500);
        }
    }

    public function updateAccountStatement(Request $request)
    {
        $validator = AccountStatementValidation::updateAccountStatement($request->all());
        if ($validator->fails()) {
            return ResponseHelper::error('Validation Error', $validator->errors(), 422);
        }

        if (!in_array(Auth::user()->role, ['sadm', 'adm'])) {
            return ResponseHelper::error('Unauthorised Action', [], 403);
        }

        $statement = AccountStatement::find($request->id);
        if (!$statement) {
            return ResponseHelper::error('Account statement not found', '', 404);
        }

        $statement->account_id   = $request->account_id ?? $statement->account_id;
        $statement->remarks      = $request->remarks ?? $statement->remarks;
        $statement->bank_id      = $request->bank_id ?? $statement->bank_id;
        $statement->credit       = $request->credit ?? $statement->credit;
        $statement->debit        = $request->debit ?? $statement->debit;
        $statement->balance      = $request->balance ?? $statement->balance;
        $statement->particulars  = $request->particulars ?? $statement->particulars;

        if ($statement->save()) {
            return ResponseHelper::success('Account statement updated successfully', $statement, 200);
        }

        return ResponseHelper::error('Something went wrong', '', 403);
    }
    public function deleteAccountStatement(Request $request)
    {
        $validator = AccountStatementValidation::deleteAccountStatement($request->all());
        if ($validator->fails()) {
            return ResponseHelper::error('Validation Failed', $validator->errors(), 422);
        }

        $statement = AccountStatement::find($request->id);
        if (!$statement) {
            return ResponseHelper::error('Account statement not found', [], 404);
        }

        if ($statement->delete()) {
            return ResponseHelper::success('Account statement deleted successfully', [], 200);
        }

        return ResponseHelper::error('Could not delete account statement', [], 403);
    }
    public function getAccountStatementSchema()
    {
        $schema = AccountStatementValidation::getAccountStatementSchema();
        return ResponseHelper::success('Schema fetched successfully', $schema, 200);
    }
}
