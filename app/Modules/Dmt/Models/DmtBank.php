<?php

namespace App\Modules\Dmt\Models;

use Illuminate\Database\Eloquent\Model;

class DmtBank extends Model
{
        protected $table = 'dmtbanks';
    /**
     * The attributes that are mass assignable.
     *
     * @var string[]
     */

    /**
     * The attributes excluded from the model's JSON form.
     *
     * @var string[]
     */
    protected $hidden = [];
}
