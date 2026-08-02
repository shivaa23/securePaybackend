<?php

namespace App\Modules\BusinessDetail\Models;

use Illuminate\Database\Eloquent\Model;

class BusinessKyc extends Model
{
    protected $table = 'business_kyc';
    protected $fillable = [
        'user_id','kyc_status','kyc_date','verified_by'
    ];
    protected $casts = [
        'kyc_date' => 'date:Y-m-d'
    ];
     public function business()
    {
        return $this->belongsTo(Business::class, 'user_id', user_id);
    }
}
