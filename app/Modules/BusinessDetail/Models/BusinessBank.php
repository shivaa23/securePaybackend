<?php

namespace App\Modules\BusinessDetail\Models;

use Illuminate\Database\Eloquent\Model;

class BusinessBank extends Model
{
    protected $table = 'business_banks';
    protected $fillable = [
        'user_id','account_holder_name','bank_name','branch_name','ifsc_code','account_number','upi_id'
    ];
     public function business()
    {
        return $this->belongsTo(Business::class, 'user_id', user_id);
    }
}
