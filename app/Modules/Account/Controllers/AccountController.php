<?php

namespace App\Modules\Account\Controllers;

use Laravel\Lumen\Routing\Controller as BaseController;
use App\Traits\ApiResponser;
use Illuminate\Http\Request;
use App\Modules\Account\Models\Account;
use App\Modules\Account\Models\AccountStatement;
use App\Modules\Bank\Models\BankStatement;
use App\Modules\User\Models\User;
use App\Modules\Account\Models\AccountValidation;
use Auth;
use DB;
use Validator;
use App\Helper\ResponseHelper;
use Illuminate\Support\Facades\Crypt;
class AccountController extends BaseController
{
    use ApiResponser;

    public function __construct(){
        $this->middleware('auth', ['except' => ['createAccount','updateAccount','encryptPayload']]);
        $this->middleware('check.active', ['except' => '']);
        $this->middleware('check.service.time',['except' => '']);
    }  
   public function getAccounts(){
    if (!in_array(Auth::user()->role, ['sadm', 'adm'])) {
        return ResponseHelper::error('Unauthorised Action', [], 403);
    }
    
    $accounts = Account::where('status', 1)
        ->when(request('user_id'), function($query) {
            $query->where('user_id', request('user_id'));
        })
        ->when(request('name'), function($query) {
            $query->where('name', request('name') );
        })
        ->when(request('mobile'), function($query) {
            $query->where('mobile',  request('mobile') );
        })
        
            ->when(request('establishment'), function($query) {
            $query->where('establishment',  request('establishment') );
        })
        ->orderBy('id', 'desc')
        ->get();
        
    return ResponseHelper::success('User accounts fetched successfully', $accounts, 200);
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
            return ResponseHelper::error($validator->errors(),'Validation Error',  422);
        }
        if (!in_array(Auth::user()->role, ['sadm', 'adm',])) {
            return ResponseHelper::error('Unauthorised Action', [], 403);
        }
        $account=Account::find($request->id);
        if (!$account) {
            return ResponseHelper::error('Account not found', '', 403);
        }
        if($account){
            // $account->name          = $request->name ?? $account->name;
            // $account->user_id       = $request->user_id ?? $account->user_id;
            // $account->establishment = $request->establishment ?? $account->establishment;
            // $account->mobile        = $request->mobile ?? $account->mobile;
            // $account->type          = $request->type ?? $account->type;
            // $account->asm    = $request->asm ?? $account->asm;
            $account->credit_limit  = $request->credit_limit ?? $account->credit_limit;
            // $account->balance       = $request->balance ?? $account->balance;
            $account->status=$request->status??$account->status;
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
       public function getAccounOthertSchema(){
        $schema = AccountValidation::getAccountOthersSchema();
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
    
    public function deleteLastTxn(Request $request){
        $rules = ['account_id' => 'required'];
        $validator = Validator::make($request->all(), $rules);
        if ($validator->fails()) {
            return ResponseHelper::error( $validator->getMessageBag()->toArray(),'Validation Error', 403);
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
}
