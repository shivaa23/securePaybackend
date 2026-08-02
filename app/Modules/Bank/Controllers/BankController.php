<?php

namespace App\Modules\Bank\Controllers;

use Laravel\Lumen\Routing\Controller as BaseController;
use App\Modules\Bank\Models\Bank;
use App\Modules\Bank\Models\BankStatement;
use App\Modules\Bank\Models\BankValidation;
use App\Traits\ApiResponser;
use Illuminate\Http\Request;
use Auth;
use DB;
use Validator;
use App\Helper\ResponseHelper;
class BankController extends BaseController
{
    use ApiResponser;
    
    public function __construct()
    {
        $this->middleware('auth', ['except' => ['']]);
        $this->middleware('check.service.time',['except' => '']);
    } 
    
    public function getBanks()
    {   
        $user = Auth::user();
        $banks = Bank::orderBy('id', 'desc')->get(); 
        return ResponseHelper::success('Banks fetched', $banks, 200);
    }
    public function getBankBalance()
    {   
        $user = Auth::user();
        if (!$user || !in_array($user->role, ['adm', 'sadm'])) {
            return ResponseHelper::error('Permission denied: Only adm or sadm can access', [], 403);
        }
        $banks = Bank::orderBy('id', 'desc')->get();
        $totalBalance = $banks->sum('balance'); // Assuming 'balance' is the column name
    
        // Return response with banks and total balance
        return ResponseHelper::success('Banks fetched', $totalBalance, 200);
    }



     public function getBankSchema()
    {
        $schema =BankValidation::bankSchema();
        return ResponseHelper::success('Banks  fetched', $schema, 200);
    }
    
    public function createBank(Request $request){
        $validator = BankValidation::createBank($request->all());
        if ($validator->fails()) {
            return ResponseHelper::error("validation error", $validator->errors(), 422);
        }
           if(!in_array(Auth::user()->role, ["sadm", "adm"])) {
             return ResponseHelper::error('Operation Not Authorised', [], 403);
        }

        $bank = new Bank();
        $bank->bank_name = $request->bank_name;
        $bank->acc_number = $request->acc_number;
        $bank->ifsc = $request->ifsc;
        $bank->balance = $request->balance;
        if($bank->save()){
            return ResponseHelper::success('Bank Created Successfully',$bank, 200);
        }else{
            return ResponseHelper::error('Bank Can Not Be Created',[], 403);
        }
    }
    public function updateBank(Request $request){
        $validator = BankValidation::updateBank($request->all());
        if ($validator->fails()) {
            return ResponseHelper::error('Failed', $validator->errors(), 422);
        }
            if(!in_array(Auth::user()->role, ["sadm", "adm"])) {
             return ResponseHelper::error('Operation Not Authorised', [], 403);
        }
        $bank = Bank::find($request->id);
        $bank->bank_name = $request->bank_name ?? $bank->bank_name;
        $bank->acc_number = $request->acc_number ?? $bank->acc_number;
        $bank->ifsc = $request->ifsc ?? $bank->ifsc;
        $bank->balance = $request->balance ?? $bank->balance;
        if($bank->save()){
            return ResponseHelper::success('Bank updated Successfully',$bank, 200);
        }else{
            return ResponseHelper::error( 'Bank Can Not Be updated','', 403);
        }
    }
 
    
     public function deleteBank(Request $request){
        $validator = BankValidation::deleteBank($request->all());
        if ($validator->fails()) {
            return $this->jsonResponse('Failed', $validator->errors(),'', 422);
        }
        if(Auth::user()->role != "sadm"){
            return ResponseHelper::error('Operation Not Authorised','', 403);
        }
        //         if(!in_array(Auth::user()->role, ["sadm", "adm"])) {
        //      return ResponseHelper::error('Operation Not Authorised', [], 403);
        // }
        $bank = Bank::find($request->id);
        if($bank->balance == 0){
            $bank->delete();
            if($bank){
                return ResponseHelper::success('Bank Deleted Successfully','', 200);
            }else{
                return ResponseHelper::error('Bank Can Not Be Deleted','', 403);
            }
        }else{
             return ResponseHelper::error('Bank Can Not Be Deleted','', 403);
        }
    }
    
    public function deleteLastTxn(Request $request){
        $rules = ['bank_id' => 'required'];
        $validator = Validator::make($request->all(), $rules);
    
        if ($validator->fails()) {
            return ResponseHelper::error('Validation Error', $validator->getMessageBag()->toArray(), 403);
        }
    
        if (Auth::user()->role != 'sadm') {
            return ResponseHelper::error('Unauthorised Access', '', 403);
        }
    
        $txn = BankStatement::where('bank_id', $request->bank_id)->latest()->first();
        if (!$txn) {
            return ResponseHelper::error('No Transaction Found To Delete', '', 403);
        }
    
        if ($txn->status == 1) {
            return ResponseHelper::error('Request Can Not Be Processed This Time As The Transaction Is Already Claimed', '', 403);
        }
    
        try {
            DB::beginTransaction();
    
            $bank = Bank::find($txn->bank_id);
    
            // Reverse the effect of the txn
            $bank->increment('balance', $txn->debit);
            $bank->decrement('balance', $txn->credit);
    
            $deleted = $txn->delete();
    
            if ($deleted) {
                DB::commit();
                return ResponseHelper::success('Transaction Deleted Successfully', '', 200);
            } else {
                DB::rollBack();
                return ResponseHelper::error('Request Can Not Be Processed This Time', '', 403);
            }
        } catch (\Exception $e) {
            DB::rollBack();
            return ResponseHelper::error('Something went wrong: '.$e->getMessage(), '', 500);
        }
    }
}
