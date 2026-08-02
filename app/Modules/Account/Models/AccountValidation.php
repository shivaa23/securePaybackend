<?php
namespace App\Modules\Account\Models;
use Illuminate\Database\Eloquent\Model;
use Validator;
use App\Modules\User\Models\User;
class AccountValidation extends Model
{
    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
	//
    ];
    
    public static function createAccount($request){
        return Validator::make($request, [
            // 'account'       => 'required|string|max:255',
             
            'asm'       => 'sometimes|integer|exists:users,id',
            'user_id'       => 'sometimes|integer|exists:users,id',
            'name'  => 'sometimes|string',
            'establishment'  => 'sometimes|string',
            'mobile'  => 'sometimes|numeric|digits:10',
            'credit_limit'  => 'nullable|numeric|min:0'

        ]);
	}
	
	public static function updateAccount($request){
        return Validator::make($request, [
            'id' => 'required|integer',
            // 'account'       => 'required|string|max:255',
            'status'        => 'nullable|in:0,1',
        ]);
	}

    public static function getAccountSchema()
    {
        // Fetch users with specific roles and select only required fields
        $users = User::whereIn('role', ["dd", "ret", "di", "md", "api"])
            ->select('id', 'name', 'mobile', 'establishment')
            ->get()
            ->map(function ($user) {
                return [
                    'name' => $user->name . ' (' . $user->mobile . ')',
                    'id' => $user->id,
                    'mobile' => $user->mobile,
                    'establishment' => $user->establishment
                ];
            })
            ->toArray();
    
        return [
            "formName" => "Account Schema",
            "fields" => [
                [
                    "name" => "user_id",
                    "label" => "Select User",
                    "type" => "autocomplete",
                    "placeholder" => "Choose a user",
                    "options" => $users,
                    "required" => true,
                    "validation" => [
                        "type" => "integer",
                        "exists" => "users,id"
                    ]
                ],
                [
                    "name" => "credit_limit",
                    "label" => "Credit Limit",
                    "type" => "number",
                    "placeholder" => "Enter credit limit (optional)",
                    "required" => false,
                    "validation" => [
                        "type" => "numeric",
                        "min" => 0,
                        "nullable" => true
                    ]
                ],
                [
                    "name" => "status",
                    "label" => "Status",
                    "type" => "dropdown",
                    "placeholder" => "Select status",
                    "required" => false,
                    "options" => [
                        ["label" => "Inactive", "value" => 0],
                        ["label" => "Active", "value" => 1]
                    ],
                    "validation" => [
                        "nullable" => true,
                        "in" => [0, 1]
                    ]
                ],
            ]
        ];
    }
        public static function getAccountOthersSchema()
    {
        // Fetch users with specific roles and select only required fields
        $users = User::whereIn('role', ['asm'])
            ->select('id', 'name', 'mobile', 'establishment')
            ->get()
            ->map(function ($user) {
                return [
                    'name' => $user->name . ' (' . $user->mobile . ')',
                    'id' => $user->id,
                    'mobile' => $user->mobile,
                    'establishment' => $user->establishment
                ];
            })
            ->toArray();
    
        return [
            "formName" => "Account Schema",
            "fields" => [
                 [
                    "name" => "name",
                    "label" => "Owner name",
                    "type" => "text",
                    "placeholder" => "Enter owner",
                    "required" => true,
                    "validation" => [
                        "type" => "string",
                        "min" => 0,
                        "nullable" => true
                    ]
                ],
                   [
                    "name" => "establishment",
                    "label" => "Shop name",
                    "type" => "text",
                    "placeholder" => "Enter shop name",
                    "required" => true,
                    "validation" => [
                        "type" => "string",
                    ]
                ],
                    [
                    "name" => "mobile",
                    "label" => "Registered mobile",
                    "type" => "text",
                    "placeholder" => "Enter mobile number",
                    "required" => true,
                    "validation" => [
                        "type" => "string",
                         "regex" => "/^[6-9]\d{9}$/" 
                    ]
                ],
                    [
                    "name" => "credit_limit",
                    "label" => "Credit Limit",
                    "type" => "number",
                    "placeholder" => "Enter credit limit (optional)",
                    "required" => false,
                    "validation" => [
                        "type" => "numeric",
                        "min" => 0,
                        "nullable" => true
                    ]
                ],
            ]
        ];
    }
    
    
    	
    public static function deleteAccount($request){ 
        return Validator::make($request, [
            'id' => 'required|integer']); }
    }

  
