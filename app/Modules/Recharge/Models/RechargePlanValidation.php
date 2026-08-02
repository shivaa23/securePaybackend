<?php

namespace App\Modules\Recharge\Models;

use Illuminate\Database\Eloquent\Model;
use Validator;

class RechargePlanValidation extends Model
{
    /**
     * Validate recharge plan data
     */
    public static function recharge($data)
    {
        return Validator::make($data, [
            'name' => 'required|string|max:255',
            'operator' => 'required|string|max:100',
            'circle' => 'required|string|max:100',
            'plan_type' => 'required|in:prepaid,postpaid',
            'price' => 'required|numeric',
            'validity_days' => 'nullable|integer',
            'data_mb' => 'nullable|integer',
            'talktime' => 'nullable|numeric',
            'sms_count' => 'nullable|integer',
        ]);
    }
    public static function rechargeSchema()
    {
        return [
            "formName" => "Recharge Plan",
            "fields" => [
                ["name" => "name", "label" => "Plan Name", "type" => "text", "required" => true],
                ["name" => "operator", "label" => "Operator", "type" => "text", "required" => true],
                ["name" => "circle", "label" => "Circle", "type" => "text", "required" => true],
                ["name" => "plan_type", "label" => "Plan Type", "type" => "select", "options" => ["prepaid", "postpaid"], "required" => true],
                ["name" => "price", "label" => "Price", "type" => "number", "required" => true],
                ["name" => "validity_days", "label" => "Validity (Days)", "type" => "number", "required" => false],
                ["name" => "data_mb", "label" => "Data (MB)", "type" => "number", "required" => false],
                ["name" => "talktime", "label" => "Talktime", "type" => "number", "required" => false],
                ["name" => "sms_count", "label" => "SMS Count", "type" => "number", "required" => false],
            ]
        ];
    }

}
