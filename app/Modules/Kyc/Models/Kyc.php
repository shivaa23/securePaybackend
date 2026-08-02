<?php

namespace App\Modules\Kyc\Models;

use Illuminate\Database\Eloquent\Model;

class Kyc extends Model 
{
    protected $table = 'kycs';

    /**
     * 
     * The attributes that are mass assignable.
     *
     * @var string[]
     */
protected $fillable = [
    'user_id',
    'pan',
    'aadhar',
    'file_url',
    'status',
];

    /**
     * The attributes excluded from the model's JSON form.
     *
     * @var string[]
     */
    protected $hidden = [  ];
    
}