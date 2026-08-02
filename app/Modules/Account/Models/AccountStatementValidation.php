<?php

namespace App\Modules\Account\Models;

use Illuminate\Database\Eloquent\Model;
use Validator;
use App\Modules\User\Models\User;
use App\Modules\Bank\Models\BankStatement;

class AccountStatementValidation extends Model
{
    protected $fillable = [];
    public static function createAccountStatement($request)
    {
        return Validator::make($request, [
            'account_id'   => 'required|integer|exists:accounts,id',
            'id'           => 'required',
            'remarks'      => 'nullable|string|max:500',
            'bank_id'      => 'required',
            'credit'       => 'nullable|numeric|min:0',
            'debit'        => 'nullable|numeric|min:0',
            'balance'      => 'required|numeric',
            'particulars'  => 'nullable|string|max:255',
        ]);
    }


    public static function updateAccountStatement($request)
    {
        return Validator::make($request, [
            'id'           => 'required|integer|exists:account_statements,id',
            'account_id'   => 'nullable|integer|exists:accounts,id',
            'remarks'      => 'nullable|string|max:500',
            'bank_id'      => 'nullable|integer|exists:banks,id',
            'credit'       => 'nullable|numeric|min:0',
            'debit'        => 'nullable|numeric|min:0',
            'balance'      => 'nullable|numeric',
            'particulars'  => 'nullable|string|max:255',
        ]);
    }


    public static function deleteAccountStatement($request)
    {
        return Validator::make($request, [
            'id' => 'required|integer|exists:account_statements,id'
        ]);
    }
      public static function getAccountStatement($request)
    {
        return Validator::make($request, [
            'account_id' => 'required|integer|exists:accounts,id'
        ]);
    }

    public static function getAccountStatementSchema()
    {
        $users = User::all(['id', 'name'])->map(function ($user) {
            return [
                'label' => $user->name,
                'value' => $user->id
            ];
        })->toArray();
     $statusmentsUnclaimed = BankStatement::where("status", 0)->get();
        $result = $statusmentsUnclaimed->toArray();
        $result[] = [
            'credit' => null,
            'value'  => null, 
        ];

// return $result;

        return [
            "formName" => "Account Statement Schema",
            "fields" => [
                 [
                    "name" => "select_transaction",
                    "label" => "Select Transaction",
                    "type" => "dropdown",
                    "placeholder" => "Select Transaction",
                    "required" => true,
                    "options" => $result,
                    // "validation" => ["type" => "str", "exists" => "accounts,id"]
                ],
                [
                    "name" => "account_id",
                    "label" => "Account",
                    "type" => "dropdown",
                    "placeholder" => "Select account",
                    "required" => true,
                    "options" => $users,
                    "validation" => ["type" => "integer", "exists" => "accounts,id"]
                ],
                [
                    "name" => "remarks",
                    "label" => "Remarks",
                    "type" => "textarea",
                    "placeholder" => "Enter remarks (optional)",
                    "required" => false,
                    "validation" => ["type" => "string", "maxLength" => 500]
                ],
                [
                    "name" => "bank_id",
                    "label" => "Bank",
                    "type" => "number",
                    "placeholder" => "Enter bank ID",
                    "required" => true,
                    "validation" => ["type" => "integer", "exists" => "banks,id"]
                ],
                [
                    "name" => "credit",
                    "label" => "Credit",
                    "type" => "number",
                    "placeholder" => "Enter credit amount",
                    "required" => false,
                    "validation" => ["type" => "numeric", "min" => 0]
                ],
                [
                    "name" => "debit",
                    "label" => "Debit",
                    "type" => "number",
                    "placeholder" => "Enter debit amount",
                    "required" => false,
                    "validation" => ["type" => "numeric", "min" => 0]
                ],
                [
                    "name" => "balance",
                    "label" => "Balance",
                    "type" => "number",
                    "placeholder" => "Enter balance",
                    "required" => true,
                    "validation" => ["type" => "numeric"]
                ],
                [
                    "name" => "particulars",
                    "label" => "Particulars",
                    "type" => "text",
                    "placeholder" => "Enter particulars (optional)",
                    "required" => false,
                    "validation" => ["type" => "string", "maxLength" => 255]
                ]
            ]
        ];
    }
}
