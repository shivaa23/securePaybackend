<?php

namespace App\Modules\Dashboard\Models;

use Illuminate\Database\Eloquent\Model;

class ServiceSummaries extends Model 
{

    /**
     * 
     * The attributes that are mass assignable.
     *
     * @var string[]
     */
     
     protected  $table="service_summaries";
    protected $fillable = [
    ];

    /**
     * The attributes excluded from the model's JSON form.
     *
     * @var string[]
     */
    protected $hidden = [  ];
    
}