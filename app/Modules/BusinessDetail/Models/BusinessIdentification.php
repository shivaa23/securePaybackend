<?php

namespace App\Modules\BusinessDetail\Models;

use Illuminate\Database\Eloquent\Model;

class BusinessIdentification extends Model
{
    protected $table = 'business_identifications';
    protected $fillable = [
        'user_id','pan_number','gst_number','udyam_number','cin_number','tan_number'
    ];
     public function business()
    {
        return $this->belongsTo(Business::class, 'user_id', user_id);
    }
}
