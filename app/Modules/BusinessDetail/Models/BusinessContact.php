<?php
namespace App\Modules\BusinessDetail\Models;

use Illuminate\Database\Eloquent\Model;

class BusinessContact extends Model
{
    protected $table = 'business_contacts';
    protected $fillable = [
        'user_id','business_email','business_mobile','alternate_mobile','website_url'
    ];
     public function business()
    {
        return $this->belongsTo(Business::class, 'user_id', user_id);
    }
}
