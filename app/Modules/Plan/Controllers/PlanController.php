<?php

namespace App\Modules\Plan\Controllers;

use Laravel\Lumen\Routing\Controller as BaseController;
use App\Traits\ApiResponser;
use Illuminate\Http\Request;
use App\Modules\Plan\Models\Plan;
use App\Modules\User\Models\User;
use App\Modules\Plan\Models\PlanValidation;
use Auth;
use App\Helper\ResponseHelper;

class PlanController extends BaseController
{
    use ApiResponser;

    public function __construct()
    {
        $this->middleware('auth', ['except' => ['']]);
        $this->middleware('check.active', ['except' => '']);

    }
    
    public function getPlans()

// {
//     $user = Auth::user();

//     if (in_array($user->role, ["sadm", "adm"])) {
//         $plans = Plan::all();
//         return ResponseHelper::success('Plans fetched', $plans, 200);
//     }

//     $plans = Plan::where("user_id", $user->id)->get();
//     return ResponseHelper::success('Plans fetched', $plans, 200);
// }
{
    if (Auth::user()->role != "sadm" && Auth::user()->role != "adm") {
        return ResponseHelper::error('Action not Authorised', '', 403);
    }
    
    $query = Plan::query();

    if (request()->has('id')) {
        $query->where('id', request('id'));
    }
    
    if (request()->has('name')) {
        $query->where('name', request('name'));
    }
    
    $plans = $query->get();
    
    if ($plans) {
        return ResponseHelper::success('Plans fetched', $plans, 200);
    } else {
        return ResponseHelper::success('No Data Found', [], 200);
    }
}
    public function getPlanSchema(){
         $schema = PlanValidation ::planSchema();
         return ResponseHelper::success('Schema fetched', $schema, 200);
    }
     public function createPlan(Request $request)
    {
       	$validator = PlanValidation::createPlan($request->all());
        if ($validator->fails()) {
            return ResponseHelper::error('Validation Failed',$validator->errors(), 422);
        }
        if(Auth::user()->role!='sadm'&&Auth::user()->role != "adm"){
            return ResponseHelper::error('Action not Authorised', '', 403);
        }
        $plan=new Plan;
        $plan->name=$request->name;
        $plan->description=$request->description;
        
        if($plan->save()){
            return ResponseHelper::success('Plan Created Successfully', $plan, 200);
        }else{
            return ResponseHelper::error('Something Went Wrong', '', 403);

        }
    }
      public function updatePlan(Request $request)
    {
       	$validator = PlanValidation::updatePlan($request->all());
        if ($validator->fails()) {
            return ResponseHelper::error('Validation Failed', $validator->errors(), 422);
        }
        if(Auth::user()->role!='sadm'&&Auth::user()->role != "adm"){
            return ResponseHelper::error('Action not Authorised', '', 403);
        }
        $plan=Plan::find($request->id);
        if($plan){
            $plan->name=$request->name??$plan->name;
            $plan->description=$request->description??$plan->description;
            if($plan->save()){
                return ResponseHelper::success('Plan updated Successfully', $plan, 200);
            }
        }else{
            return ResponseHelper::error('Something Went Wrong', '', 403);

        }
    }
    public function asignPlan(Request $request)
    {
        $validator = PlanValidation::asignPlan($request->all());
        if ($validator->fails()) {
            return ResponseHelper::error('Validation Failed', $validator->errors(), 422);
        }
    
        if (!in_array(Auth::user()->role, ['sadm', 'adm'])) {
            return ResponseHelper::error('Action not Authorised', '', 403);
        }
    
        $user = User::find($request->user_id);
        if (!$user) {
            return ResponseHelper::error('User not found', '', 404);
        }
    
        $user->plan_id = $request->plan_id;
        if ($user->save()) {
            return ResponseHelper::success('Plan updated successfully', $user, 200);
        } else {
            return ResponseHelper::error('Something went wrong while saving the plan', '', 500);
        }
    }
    public function deletePlan(Request $request)
    {
        $validator = PlanValidation::deletePlan($request->all());
        if ($validator->fails()) {
            return ResponseHelper::error('Validation Failed', $validator->errors(), 422);
        }
    
        if (!in_array(Auth::user()->role, ['sadm', 'adm'])) {
            return ResponseHelper::error('Action not Authorised', '', 403);
        }
        $isAssigned = User::where('plan_id', $request->id)->exists();
        if ($isAssigned) {
            return ResponseHelper::error('This plan is currently assigned to one or more users and cannot be deleted..', '', 403);
        }
        $plan = Plan::find($request->id);
        $plan->delete();
        return ResponseHelper::success('Plan deleted successfully.', '', 200);
    }


}
