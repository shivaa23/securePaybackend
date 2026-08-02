<?php
namespace App\Modules\BusinessDetail\Models;
use Illuminate\Database\Eloquent\Model;
class BusinessAddress extends Model
{
    protected $table = 'business_addresses';
    protected $fillable = [
        'user_id','address_line1','address_line2','city','state','pincode','country'
    ];
    
   public function business()
    {
        return $this->belongsTo(Business::class, 'user_id', user_id);
    }
}
