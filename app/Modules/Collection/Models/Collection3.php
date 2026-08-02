<?php

namespace App\Modules\Collection\Models;

use Illuminate\Database\Eloquent\Model;

class Collection3 extends Model 
{
    /**
     * Define table name
     *
     * @var string
     */
    protected $table = 'collections3';

    protected $fillable = ['status'
        // add your fields here
    ];

    protected $hidden = [  
        // hidden fields
    ];
}
