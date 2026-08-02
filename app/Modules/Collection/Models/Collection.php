<?php

namespace App\Modules\Collection\Models;

use Illuminate\Database\Eloquent\Model;

class Collection extends Model 
{
    /**
     * Define table name
     *
     * @var string
     */
    protected $table = 'collections';

    protected $fillable = [
        // add your fields here
    ];

    protected $hidden = [  
        // hidden fields
    ];
}
