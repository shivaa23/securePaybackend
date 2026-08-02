<?php

namespace App\Modules\LayoutModule\Models;

use Illuminate\Database\Eloquent\Model;

class SideNav extends Model 
{

    /**
     * 
     * The attributes that are mass assignable.
     *
     * @var string[]
     */
    protected $fillable = [
    ];

    /**
     * The attributes excluded from the model's JSON form.
     *
     * @var string[]
     */
    protected $hidden = [  ];
     protected $casts = [
        'role' => 'array', // converts JSON <-> array automatically
    ];
    
}