<?php
    namespace App\Modules\Commission\Models;
use Illuminate\Database\Eloquent\Model;
use Validator;
use App\Modules\Plan\Models\Plan;
use App\Modules\Service\Models\Service;
use App\Modules\Admin\Models\Route;
class CommissionValidation extends Model
{
    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
	//
    ];
      public static function createCommissionRule($request){
        return Validator::make($request, [
           'plan_id'      => 'required|integer|exists:plans,id',  
            'service_name' => 'required|string|max:255',
          
            'rule_type'    => 'required|string', 
            'value_type'   => 'required|string',
            
            'comm_dd'      => 'required|numeric',
            'comm_ret'     => 'required|numeric',
            'comm_di'      => 'required|numeric',
            'comm_md'      => 'required|numeric',
            
            'min_amount'   => 'required|numeric|min:0',
            'max_amount'   => 'required|numeric|gte:min_amount',
        ]);
	}
	
        public static function updateCommissionRule($request){
            return Validator::make($request, [
                'id'           => 'required|integer', // must exist in commission_rules
                'plan_id'      => 'sometimes|integer|exists:plans,id',  
                'service_name' => 'sometimes|string|max:255',
                
                'rule_type'    => 'sometimes|string', 
                'value_type'   => 'sometimes|string',
                
                'comm_dd'      => 'sometimes|numeric',
                'comm_ret'     => 'sometimes|numeric',
                'comm_di'      => 'sometimes|numeric',
                'comm_md'      => 'sometimes|numeric',
                
                'min_amount'   => 'sometimes|numeric|min:0',
                'max_amount'   => 'sometimes|numeric|gte:min_amount',
            ]);
        }
            public static function createAdminCommissionRule($request){
        return Validator::make($request, [
            'service_name' => 'required|string|max:255',
          
            'rule_type'    => 'required|string', 
            'value_type'   => 'required|string',
            'a_comm'      => 'required|numeric',
            
            'min_amount'   => 'required|numeric|min:0',
            'max_amount'   => 'required|numeric|gte:min_amount',
        ]);
	}
	
        public static function updateAdminCommissionRule($request){
            return Validator::make($request, [
                'id'           => 'required|integer|exists:acomm_rules,id', // must exist in commission_rules
         
                'service_name' => 'sometimes|string|max:255',
                
                'rule_type'    => 'sometimes|string', 
                'value_type'   => 'sometimes|string',
                'a_comm'      => 'sometimes|numeric',
                
                'min_amount'   => 'sometimes|numeric|min:0',
                'max_amount'   => 'sometimes|numeric|gte:min_amount',
            ]);
        }
          public static function deleteCommissionRule($request){
            return Validator::make($request, [
                'id'           => 'required|integer|exists:comm_rules,id', 
            ]);
        }

	public static function getSchema()
    {
    $plan = Plan::all();
     $service = Service::orderBy('name', 'asc')->get();

      return [
        "formName" => "Commission",
        "fields" => [
            [
                "name" => "plan_id",
                "label" => "Plan",
                "type" => "dropdown",
                "placeholder" => "select your plan",
                "options"=>$plan,
                "required" => true,
                "validation" => ["type" => "integer", "exists" => "plans,id"]
            ],
            [
                "name" => "service_name",
                "label" => "Service Name",
                "type" => "autocomplete",
                "placeholder" => "Enter service name",
                "required" => true,
               "options"=>$service,
                "validation" => ["type" => "string", "maxLength" => 255]
            ],
            
            [
                "name" => "rule_type",
                "label" => "Rule Type",
                "type" => "dropdown",
                "placeholder" => "Select rule type",
                "required" => true,
                "options" => ["commission", "charge"], 
                "validation" => ["type" => "string"]
            ],
            [
                "name" => "value_type",
                "label" => "Value Type",
                "type" => "dropdown",
                "placeholder" => "Select value type",
                "required" => true,
                "options" =>["percentage", "flat"] ,
                "validation" => ["type" => "string"]
            ],
            [
                "name" => "comm_di",
                "label" => "Distributor Commission",
                "type" => "number",
                "placeholder" => "Enter distributor commission",
                "required" => true,
                "validation" => ["type" => "numeric"]
            ],
            [
                "name" => "comm_ret",
                "label" => "Retailer Commission",
                "type" => "number",
                "placeholder" => "Enter retailer commission",
                "required" => true,
                "validation" => ["type" => "numeric"]
            ],
            [
                "name" => "comm_dd",
                "label" => "Direct Dealer",
                "type" => "number",
                "placeholder" => "Enter direct commission",
                "required" => true,
                "validation" => ["type" => "numeric"]
            ],
            [
                "name" => "comm_md",
                "label" => "Master Distributor Commission",
                "type" => "number",
                "placeholder" => "Enter master distributor commission",
                "required" => true,
                "validation" => ["type" => "numeric"]
            ],
            [
                "name" => "min_amount",
                "label" => "Minimum Amount",
                "type" => "number",
                "placeholder" => "Enter minimum transaction amount",
                "required" => true,
                "validation" => ["type" => "numeric", "min" => 0]
            ],
            [
                "name" => "max_amount",
                "label" => "Maximum Amount",
                "type" => "number",
                "placeholder" => "Enter maximum transaction amount",
                "required" => true,
                "validation" => ["type" => "numeric", "gte" => "min_amount"]
            ]
        ]
    ];
}
	public static function getAdminCommSchema()
    {
     $service = Service::orderBy('name', 'asc')->get();
      $route = Route::all();
      return [
        "formName" => "Commission",
        "fields" => [
            [
                "name" => "service_name",
                "label" => "Service Name",
                "type" => "autocomplete",
                "placeholder" => "Enter service name",
                "required" => true,
               "options"=>$service,
                "validation" => ["type" => "string", "maxLength" => 255]
            ],
                 [
                "name" => "route",
                "label" => "Route",
                "type" => "autocomplete",
                "placeholder" => "Enter service name",
                "required" => true,
                 "options"=>$route,
                "validation" => ["type" => "string", "maxLength" => 255]
            ],
            
            [
                "name" => "rule_type",
                "label" => "Rule Type",
                "type" => "dropdown",
                "placeholder" => "Select rule type",
                "required" => true,
                "options" => ["commission", "charge"], 
                "validation" => ["type" => "string"]
            ],
            [
                "name" => "value_type",
                "label" => "Value Type",
                "type" => "dropdown",
                "placeholder" => "Select value type",
                "required" => true,
                "options" =>["percentage", "flat"] ,
                "validation" => ["type" => "string"]
            ],
            [
                "name" => "a_comm",
                "label" => "Admin Commission",
                "type" => "number",
                "placeholder" => "Enter admin commission",
                "required" => true,
                "validation" => ["type" => "numeric"]
            ],
            [
                "name" => "min_amount",
                "label" => "Minimum Amount",
                "type" => "number",
                "placeholder" => "Enter minimum transaction amount",
                "required" => true,
                "validation" => ["type" => "numeric", "min" => 0]
            ],
            [
                "name" => "max_amount",
                "label" => "Maximum Amount",
                "type" => "number",
                "placeholder" => "Enter maximum transaction amount",
                "required" => true,
                "validation" => ["type" => "numeric", "gte" => "min_amount"]
            ]
        ]
    ];
}

}