<?php

namespace App\Modules\Risk\Models;

use Illuminate\Support\Facades\Validator;

class RiskValidation
{
    public static function whiteList(array $data, $id = null)
    {
        $rules = [
            'acc_no'     => 'required|string',
            'acc_name'   => 'required|string|max:150',
            'acc_type'   => 'required|in:saving,current',
            'limit'      => 'required|numeric|min:0',
        ];

        // Add unique rule only if it's a new record, or ignore current record for updates
        if ($id) {
            $rules['acc_no'] .= '|unique:white_listed_accs,acc_no,' . $id;
        } else {
            $rules['acc_no'] .= '|unique:white_listed_accs,acc_no';
        }

        return Validator::make($data, $rules);
    }

    public static function blackList(array $data, $id = null)
    {
        $rules = [
            'acc_no' => 'required|string',
        ];

        // Add unique rule with ignore for updates
        if ($id) {
            $rules['acc_no'] .= '|unique:black_listed_accs,acc_no,' . $id;
        } else {
            $rules['acc_no'] .= '|unique:black_listed_accs,acc_no';
        }

        return Validator::make($data, $rules);
    }
}