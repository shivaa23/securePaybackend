<?php

namespace App\Modules\Commission\Models;
use Illuminate\Database\Eloquent\Model;
use App\Helper\ResponseHelper;
//use App\Modules\Commission\Models\CommRule;
use App\Modules\User\Models\User;

class CommRule extends Model 
{
    
    public static function calculateCommission($user, $service, $amount) {
        
        $commissions = [];
        $di = User::find($user->parent);   // ret → di
        $md = $di ? User::find($di->parent) : null; // di → md
        if ($md && $md->plan_id) {
            $planId = $md->plan_id;
        } elseif ($di && $di->plan_id) {
            $planId = $di->plan_id;
        } else {
            $planId = $user->plan_id;
        }
        //$commissions = []; 
        //$planId = $user->plan_id;
        $rule = CommRule::where('plan_id', $planId)
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
        if ($user->role === 'dd') {
            $commission = 0;
            $value = $rule->comm_dd ?? 0;
            if ($value > 0) {
                $commission = ($rule->value_type == 'flat') ? $value : ($amount * $value) / 100;
            }
            $commissions[$user->role] = $commission;
            $commissions['type'] = $rule->value_type;
            $commissions['rule_type'] = $rule->rule_type;
            return $commissions;
        }
        while ($user) {
            $commission = 0;
            switch ($user->role) {
                case 'ret':
                    $value = $rule->comm_ret;
                    break;
                case 'di':
                    $value = $rule->comm_di;
                    break;
                case 'md':
                    $value = $rule->comm_md;
                    break;
                case 'dd':
                    $value = $rule->comm_dd;
                    break;
                default:
                    $value = 0;
            }
    
            if ($value > 0) {
                $commission = ($rule->value_type == 'flat') ? $value : ($amount * $value) / 100;
            }
            $commissions[$user->role] = $commission;
            $commissions['type'] = $rule->value_type;
            $commissions['rule_type'] = $rule->rule_type;
            $user = User::find($user->parent);
        }
        return $commissions;
    }
    
    public static function finalCommission($comm, $amount, $service){
        if(in_array($service->id, [24,50])){
            // if(isset($comm['di'])){
            //     $comm['di'] = $comm['di'];
            // }
            // if(isset($comm['md'])){
            //     $comm['md'] = $comm['md'];
            // }
            return $comm;
        }
        if($comm['type'] == 'flat'){
            if(isset($comm['di'])){
                $comm['di'] = $amount * $comm['di']/100;
            }
            if(isset($comm['md'])){
                $comm['md'] = $amount * $comm['md']/100;
            }
        }
        return $comm;
    }
}