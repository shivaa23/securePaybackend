<?php

namespace App\Modules\Commission\Controllers;

use Laravel\Lumen\Routing\Controller as BaseController;
use App\Traits\ApiResponser;
use Illuminate\Http\Request;
use App\Modules\Commission\Models\AcommRule;
use App\Modules\Recharge\Models\RechargeTxn;
use App\Modules\User\Models\User;
use App\Modules\Commission\Models\CommissionValidation;
use Auth;
use App\Helper\ResponseHelper;

class AdminCommissionController extends BaseController
{
    use ApiResponser;

    public function __construct()
    {
        $this->middleware('auth', ['except' => ['disburseCommRecharge']]);
        $this->middleware('check.active', ['except' => 'disburseCommRecharge']);

    }
   public function getCommissions(Request $request)
   {
        if (!in_array(Auth::user()->role, ['sadm', 'adm'])) {
            return ResponseHelper::error('Action not Authorised', '', 403);
        }
        $query = AcommRule::query();
        if ($request->filled('service_name')) {
            $query->where('service_name', $request->input('service_name'));
        }
        if ($request->filled('rule_type')) {
            $query->where('rule_type', $request->input('rule_type'));
        }
        $commissionchargerule = $query->get();
    
        return ResponseHelper::success('Commission Charge Rules fetched', $commissionchargerule, 200);
    }

       public function getAdminCommissionSchema(Request $request)
        {
            
            
                 	$schema = CommissionValidation::getAdminCommSchema();
                 	 return ResponseHelper::success('Schema found', $schema, 200);
            
        }
      public function createCommissionRule(Request $request)
    {
       	$validator = CommissionValidation::createAdminCommissionRule($request->all());
        if ($validator->fails()) {
            return ResponseHelper::error('Validation Error', $validator->errors(), 422);
        }
          if(Auth::user()->role != "sadm"&&Auth::user()->role != "adm"){
             return ResponseHelper::error('Action not Authorised', '', 403);
        }
        $commission=new AcommRule();
        // $commission->plan_id=$request->plan_id;
        $commission->service_name=$request->service_name;
        $commission->rule_type=$request->rule_type;
        $commission->route = $request->route;
        $commission->value_type = $request->value_type;
        $commission->a_comm = $request->a_comm;
        $commission->min_amount = $request->min_amount;
        $commission->max_amount = $request->max_amount;

        if($commission->save()){
            return ResponseHelper::success('Commission Rule Created Successfully',$commission, 200);
        }else{
            return ResponseHelper::error('Something Went Wrong', '', 403);

        }
    }
   public function updateCommissionRule(Request $request)
    {
       	$validator = CommissionValidation::updateCommissionRule($request->all());
        if ($validator->fails()) {
            return ResponseHelper::error('Validation Error', $validator->errors(), 422);
        }
          if(Auth::user()->role != "sadm"&&Auth::user()->role != "adm"){
             return ResponseHelper::error('Action not Authorised', '', 403);
        }
        $commission = AcommRule::find($request->id);
        $commission->service_name=$request->service_name??$commission->service_name;
        $commission->rule_type=$request->rule_type??$commission->rule_type;
        $commission->route=$request->route??$commission->route;
        $commission->value_type=$request->value_type??$commission->value_type;
        $commission->a_comm=$request->a_comm??$commission->a_comm;
        $commission->min_amount=$request->min_amount??$commission->min_amount;
        $commission->max_amount=$request->max_amount??$commission->max_amount;

        if($commission->save()){
            return ResponseHelper::success('Commission Rule Updated Successfully',$commission, 200);
        }else{
            return ResponseHelper::error('Something Went Wrong', '', 403);

        }
    }
       public function deleteAdminCommissionRule(Request $request)
    {
       	$validator = CommissionValidation::deleteCommissionRule($request->all());
        if ($validator->fails()) {
            return ResponseHelper::error('Validation Error', $validator->errors(), 422);
        }
          
        $commission = AcommRule::find($request->id);
        $commission->delete();
        return ResponseHelper::success('Commission Rule deleted  Successfully',[], 200);

    }
    
    public function disburseCommRecharge(){
        $users = User::whereIn('role',['api','ret','dd'])->get();
        //return $users;
        foreach($users as $user){
            $data['user_id'] = $user->id;
            $data['ret_comm'] = RechargeTxn::whereIn('status',['SUCCESS','PENDING'])->where('comm_status',1)->where('user_id',$user->id)->sum('comm');
        }
        return $data;
        
    }

}
