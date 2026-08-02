<?php
namespace App\Modules\Bank\Models;
use Illuminate\Database\Eloquent\Model;
use Validator;

class BankValidation extends Model
{
    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
	//
    ];
    
    public static function createBank($request){
        return Validator::make($request, [
            'bank_name' => 'required|string',
            'acc_number' => 'required|numeric|unique:banks',
            'ifsc' => 'required|string|size:11|unique:banks|regex:/^[A-Z]{4}0[0-9A-Z]{6}$/',
            'balance' => 'required|numeric',
        ]);
	}
	public static function updateBank($request){
        return Validator::make($request, [
            'id' => 'required|numeric',
            'bank_name' => 'sometimes|string',
            'acc_number' => 'sometimes|numeric|unique:banks',
            'ifsc' => 'sometimes|string|size:11|unique:banks|regex:/^[A-Z]{4}0[0-9A-Z]{6}$/',
            'balance' => 'sometimes|numeric',
        ]);
	}
	  public static function bankSchema()
    {
        
        return [
            "formName" => "Create Bank",
            "fields" => [
                [
                    "name" => "bank_name",
                    "label" => "Bank Name",
                    "type" => "text",
                    "placeholder" => "Enter bank name",
                    "required" => true,
                    "validation" => ["type" => "string"]
                ],
                [
                    "name" => "acc_number",
                    "label" => "Account Number",
                    "type" => "number",
                    "placeholder" => "Enter account number",
                    "required" => true,
                    "validation" => ["type" => "numeric", "unique" => true]
                ],
                [
                    "name" => "ifsc",
                    "label" => "IFSC Code",
                    "type" => "text",
                    "placeholder" => "Enter IFSC code",
                    "required" => true,
                    "validation" => [
                        "type" => "string",
                        "size" => 11,
                        "regex" => "^[A-Z]{4}0[0-9A-Z]{6}$",
                        "unique" => true,
                        "errorMessage" => "Must be a valid IFSC code"
                    ]
                ],
                [
                    "name" => "balance",
                    "label" => "Balance",
                    "type" => "number",
                    "placeholder" => "Enter balance",
                    "required" => true,
                    "validation" => ["type" => "numeric"]
                ]
            ]
        ];
    }
	public static function deleteBank($request){
        return Validator::make($request, [
            'id' => 'required|numeric'
        ]);
	}
}