<?php

namespace App\Modules\LayoutModule\Models;

use Illuminate\Database\Eloquent\Model;

class Colour extends Model 
{

    /**
     * 
     * The attributes that are mass assignable.
     *
     * @var string[]
     */
  protected $fillable = [
        'name',         // e.g., 'Header Color'
        'color_code',   // e.g., '#FF5733'
        'element_type'  // e.g., 'header', 'button', 'text'
    ];
    /**
     * The attributes excluded from the model's JSON form.
     *
     * @var string[]
     */
    protected $hidden = [  ];
    
}