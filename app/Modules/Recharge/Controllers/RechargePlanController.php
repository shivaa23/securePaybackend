<?php

namespace App\Modules\Recharge\Controllers;

use Laravel\Lumen\Routing\Controller as BaseController;
use Illuminate\Http\Request;
use App\Helper\ResponseHelper;
use App\Modules\Recharge\Models\RechargePlan;
use App\Modules\Recharge\Models\RechargePlanValidation;
use App\Modules\Service\Models\Service;
use App\Modules\Integration\Models\Mplan;
class RechargePlanController extends BaseController
{
    public function __construct()
    {
        $this->middleware('auth', ['except' => []]);
        $this->middleware('check.active', ['except' => []]);
    }

    /**
     * Create a new recharge plan
     */
    public function createRechargePlan(Request $request)
    {

        $validator = RechargePlanValidation::recharge($request->all());
        if ($validator->fails()) {
            return ResponseHelper::error("validation error", $validator->errors(), 422);
        }

        $plan = new RechargePlan();
        $plan->name = $request->name;
        $plan->operator = $request->operator;
        $plan->circle = $request->circle;
        $plan->plan_type = $request->plan_type;
        $plan->price = $request->price;
        $plan->validity_days = $request->validity_days ?? null;
        $plan->data_mb = $request->data_mb ?? null;
        $plan->talktime = $request->talktime ?? null;
        $plan->sms_count = $request->sms_count ?? null;

        $plan->save();

        return ResponseHelper::success('Plans created ' ,$plan, 200);
    }
    
    public function getPlansByOperator(Request $request)
    {
        $operator = $request->operator;
    
        if (!$operator) {
            return ResponseHelper::error('Operator is required', [], 422);
        }
    
        // Fetch plans for this operator (case-insensitive)
        $plans = RechargePlan::whereRaw('LOWER(operator) = ?', [strtolower($operator)])->get();
    
        if ($plans->isEmpty()) {
            return ResponseHelper::error('No plans found for this operator', [], 404);
        }
    
        return ResponseHelper::success('Plans fetched for operator: ' . ucfirst(strtolower($operator)), $plans, 200);
    }
    public static function getPlanByCircle(Request $request)
    {
        
        $service = Service::find($request->operator);
        $url = 'https://www.mplan.in/api/plans.php';
        $payload = [
            'apikey'   => env('MPLANAPIKEY'),
            'cricle'   => $data->circle,
            'operator' => $service->mplan,
        ];
        $indicoreResponse = Mplan::service($url, 'GET', $payload);
    
        if (!$indicoreResponse) {
            return [
                'status'   => 'FAILED',
                'response' => 'No response from mPlan API',
            ];
        }
    
        // Decode JSON response
        $responseData = json_decode($indicoreResponse, true);
    
        if (!$responseData) {
            return [
                'status'   => 'FAILED',
                'response' => 'Invalid JSON from mPlan API',
            ];
        }
    
        return [
            'status'   => 'SUCCESS',
            'response' => $responseData,
        ];
    }

     public function getRechargePlans(Request $request)
    {
  // Fetch plans for this operator (case-insensitive)
        $plans = RechargePlan::all();
        return ResponseHelper::success('Plans fetched for operator ', $plans, 200);
    }
     
         public function getPlansSchema(Request $request){
            $schema = RechargePlanValidation::rechargeSchema();
             return ResponseHelper::success('Plans Schema fetched', $schema, 200);
            
    }


}
