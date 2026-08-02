<?php

namespace App\Modules\LayoutModule\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Validator;

class SideNavValidation extends Model
{
    public static function createSideNav(array $data)
    {
        return Validator::make($data, [
            'name'  => 'required|string|max:255',
            'url'   => 'required|string|max:255',
            'title' => 'required|string|max:255',
            'role'  => 'required|array', // must be an array
            'role.*'=> 'string|in:sadm,adm,ret,dd,asm,zsm,ad,md', // each item must be valid
        ]);
    }

    public static function updateSideNav(array $data)
    {
        return Validator::make($data, [
            'id'    => 'required|exists:side_navs,id',
            'name'  => 'sometimes|string|max:255',
            'title' => 'sometimes|string|max:255',
            'url'   => 'sometimes|string|max:255',
            'role'  => 'sometimes|array',
            'role.*'=> 'string|in:sadm,adm,ret,dd,asm,zsm,ad,md',
        ]);
    }

    public static function sideNavSchema()
    {
        return [
            "formName" => "Create Layout Module",
            "fields" => [
                [
                    "name" => "name",
                    "label" => "Module Name",
                    "type" => "text",
                    "placeholder" => "Enter module name",
                    "required" => true,
                    "validation" => ["type" => "string", "max" => 255]
                ],
                [
                    "name" => "url",
                    "label" => "Module URL",
                    "type" => "text",
                    "placeholder" => "Enter module URL",
                    "required" => true,
                    "validation" => ["type" => "string", "max" => 255]
                ],
                [
                    "name" => "title",
                    "label" => "Title name",
                    "type" => "text",
                    "placeholder" => "Enter title name",
                    "required" => true,
                    "validation" => ["type" => "string", "max" => 255]
                ],
                [
                    "name" => "role",
                    "label" => "Role",
                    "type" => "multiselect", // changed from select → multiselect
                    "placeholder" => "Select role(s)",
                    "required" => true,
                    "options" => [
                        "sadm" => "Super Admin",
                        "adm"  => "Admin",
                        "ret"  => "Retailer",
                        "dd"   => "Distributor",
                        "asm"  => "ASM",
                        "zsm"  => "ZSM",
                        "ad"   => "AD",
                        "md"   => "MD"
                    ],
                    "validation" => [
                        "type" => "array",
                        "items" => ["type" => "string", "in" => ["sadm","adm","ret","dd","asm","zsm","ad","md"]]
                    ]
                ]
            ]
        ];
    }
}
