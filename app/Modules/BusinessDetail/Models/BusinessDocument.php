<?php

namespace App\Modules\BusinessDetail\Models;

use Illuminate\Database\Eloquent\Model;

class BusinessDocument extends Model
{
    protected $table = 'business_documents';
    protected $fillable = [
        'user_id','gst_certificate','udyam_certificate','shop_license','rent_agreement','electricity_bill'
    ];
     public function business()
    {
        return $this->belongsTo(Business::class, 'user_id', user_id);
    }
}
