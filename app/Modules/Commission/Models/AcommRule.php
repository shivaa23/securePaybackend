<?php

namespace App\Modules\Commission\Models;
use Illuminate\Database\Eloquent\Model;
use App\Helper\ResponseHelper;
//use App\Modules\Commission\Models\CommRule;
use App\Modules\User\Models\User;

class AcommRule extends Model 
{
     protected $table = 'acomm_rules';
    public static function calculateAdminCommission($service, $amount, $route) {
        
        $commissions = [];
        $rule = AcommRule::where('route', $route)
            ->where('service_name', $service)
            ->where(function ($q) use ($amount) {
                $q->where('min_amount', '<=', $amount)
                  ->where(function ($qq) use ($amount) {
                      $qq->where('max_amount', '>=', $amount)
                         ->orWhere('max_amount', 0);
                  });
            })
            ->first();
    
        if (!$rule) {
             //return ResponseHelper::error('No Comercial configured', '', 403);
            return $commissions;
        }
        $value = $rule->a_comm;
        $commission = 0;
        if ($value > 0) {
            $commission = $value;
        }
        $commissions['a_comm'] = $commission;
        $commissions['type'] = $rule->value_type;
        $commissions['rule_type'] = $rule->rule_type;
        return $commissions;
    }
    
    public static function finalCommission($comm, $amount){
            if($comm['type'] == 'flat'){
                $comm = $comm['a_comm'];
            }else{
                $comm = $amount * $comm['a_comm']/100;
            }
        return $comm;
    }
}