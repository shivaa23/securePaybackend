<?php

namespace App\Modules\LayoutModule\Models;

use Illuminate\Support\Facades\Validator;
use Illuminate\Database\Eloquent\Model;

class ColorsValidator extends Model
{
    /**
     * Validate data for creating a color
     */
    public static function createColour( $data)
    {
        return Validator::make($data, [
            'name' => 'required|string|max:100',
            'color_code' => 'required|string|max:20',
            'element_type' => 'required|string|max:50',
        ]);
    }

    /**
     * Validate data for updating a color
     */
public static function colorSchema()
{
    return [
        "formName" => "Create Color",
        "fields" => [
                   [
                "name" => "name",
                "label" => "Name",
                "type" => "dropdown",
                "placeholder" => "Select name",
                "required" => true,
                   "options" => [
                    [ "value" => "header","name" => "Header"],
                    [ "value" => "sidenav","name" => "Side Navigation"],
                    [ "value" => "button","name" => "Button"],
                    [ "value" => "background_text_color","name" => "Background Text Color"],
                    [ "value" => "table_color","name" => "Table Color"],
                ],
                "validation" => ["type" => "string", "max" => 100]
            ],
                  [
                "name" => "element_type",
                "label" => "Element Type",
                "type" => "text", // changed to dropdown
                "placeholder" => "Enter element type",
                "required" => true,
             
                "validation" => ["type" => "string", "max" => 50]
            ],
     
            [
                "name" => "color_code",
                "label" => "Color Code",
                "type" => "color",
                "placeholder" => "Enter color code",
                "required" => true,
                "validation" => ["type" => "string", "max" => 20]
            ],
      
        ]
    ];
}



    public static function updateColour( $data)
    {
        return Validator::make($data, [
              'id' => 'required|exists:colours,id',
            'name' => 'sometimes|required|string|max:100',
            'color_code' => 'sometimes|required|string|max:20',
            'element_type' => 'sometimes|required|string|max:50',
        ]);
    }
}
