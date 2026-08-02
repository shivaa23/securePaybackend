<?php

namespace App\Modules\Bank\Models;

use Validator;

class BankStatementValidation
{
    public static function createBankStatement($request)
{
    $validator = Validator::make($request, [
        'bank_id'     => 'required|integer',
        'particulars' => 'required|string',
        'credit'      => 'nullable|numeric',
        'debit'       => 'nullable|numeric',
        'status'      => 'sometimes|string|in:pending,completed,failed',
        'balance'     => 'sometimes|numeric',
        'mop'         => 'required|string',
        'date'        => 'required|date',
    ]);

    // Add custom validation
    $validator->after(function ($validator) use ($request) {
        $credit = $request['credit'] ?? 0;
        $debit  = $request['debit'] ?? 0;

        if ($credit > 0 && $debit > 0) {
            $validator->errors()->add('debit', 'If credit is greater than 0, debit must be 0.');
        }

        if ($debit > 0 && $credit > 0) {
            $validator->errors()->add('credit', 'If debit is greater than 0, credit must be 0.');
        }
    });

    return $validator;
}


    public static function updateBankStatement($request)
    {
        return Validator::make($request, [
            'id'          => 'required|integer|exists:bank_statements,id',
            'bank_id'     => 'nullable|integer',
            'particulars' => 'nullable|string',
            'claimed_by'  => 'nullable|string',
            'credit'      => 'nullable|numeric',
            'debit'       => 'nullable|numeric',
            'balance'     => 'nullable|numeric',
            'status'      => 'sometimes|string|in:pending,completed,failed',
            'date'        => 'nullable|date',
        ]);
    }

    public static function deleteBankStatement($request)
    {
        return Validator::make($request, [
            'id' => 'required|integer|exists:bank_statements,id',
        ]);
    }
    public static function getBankStatement($request)
    {
        return Validator::make($request, [
            'bank_id' => 'required|integer|exists:banks,id',
        ]);
    }
    public static function bankSchema()
    {
        require_once base_path('app/Modules/Utils/getModes.php'); 
         $modes = getModes();
        return [
            "formName" => "Bank Statement Schema",
            "fields"   => [
                ["name" => "particulars", "label" => "Particulars", "type" => "text", "required" => true],
                ["name" => "credit", "label" => "Credit", "type" => "number", "required" => false],
                ["name" => "debit", "label" => "Debit", "type" => "number", "required" => false],
                [
                "name" => "mop",
                "label" => "MOP",
                "type" => "dropdown",
                "placeholder" => "Select mode",
                "required" => true,
                "options" => $modes, 
                "validation" => ["type" => "string"]
            ],
                ["name" => "balance", "label" => "Balance", "type" => "number", "required" => true],
                ["name" => "status", "label" => "Status", "type" => "text", "required" => true],
                ["name" => "date", "label" => "Date", "type" => "date", "required" => true],
                // ["name" => "remark", "label" => "Remark", "type" => "text", "required" => false],
            ]
        ];
    }
}
