<?php

namespace App\Modules\Plan\Models;

use Illuminate\Database\Eloquent\Model;
use Validator;

class PlanValidation extends Model
{
    protected $fillable = [];

    public static function createPlan($request)
    {
        return Validator::make($request, [
            'name' => 'required|string|max:255',
            'description' => 'required|string|min:10|max:1000',
        ]);
    }

    public static function updatePlan($request)
    {
        return Validator::make($request, [
            'id' => 'required|integer',
            'name' => 'sometimes|string|max:255',
            'description' => 'sometimes|string|min:10|max:1000',
        ]);
    }
    public static function asignPlan(array $request)
    {
        return Validator::make($request, [
            'user_id' => 'required|integer|exists:users,id',
            'plan_id' => 'required|integer|exists:plans,id',
        ]);
    }
     public static function deletePlan(array $request)
    {
        return Validator::make($request, [
            'id' => 'required|integer|exists:plans,id',
        ]);
    }
    

    public static function planSchema()
    {
        return [
            "formName" => "Create Plan",
            "fields" => [
                [
                    "name" => "name",
                    "label" => "Plan Name",
                    "type" => "text",
                    "placeholder" => "Enter plan name",
                    "required" => true,
                    "validation" => [
                        "type" => "string",
                        "minLength" => 3,
                        "maxLength" => 255
                    ]
                ],
                [
                    "name" => "description",
                    "label" => "Plan Description",
                    "type" => "textarea",
                    "placeholder" => "Enter plan description",
                    "required" => true,
                    "validation" => [
                        "type" => "string",
                        "minLength" => 2,
                        "maxLength" => 1000
                    ]
                ],
            ]
        ];
    }
}
