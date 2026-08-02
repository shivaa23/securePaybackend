<?php

namespace App\Modules\Aeps\Models;

use Illuminate\Database\Eloquent\Model;

class AepsBank extends Model 
{
    /**
     * The attributes that are mass assignable.
     *
     * @var string[]
     */
     protected $table = 'aepsbanks';
    protected $fillable = [
    ];

    /**
     * The attributes excluded from the model's JSON form.
     *
     * @var string[]
     */
    protected $hidden = [];
}
