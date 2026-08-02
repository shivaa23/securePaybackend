<?php
namespace App\Modules\BusinessDetail\Models;

use Illuminate\Database\Eloquent\Model;
use App\Modules\BusinessDetail\Models\BusinessContact;
use App\Modules\BusinessDetail\Models\BusinessAddress;
use App\Modules\BusinessDetail\Models\BusinessIdentification;
use App\Modules\BusinessDetail\Models\BusinessBank;
use App\Modules\BusinessDetail\Models\BusinessDocument;
use App\Modules\BusinessDetail\Models\BusinessKyc;
use App\Modules\BusinessDetail\Models\BusinessStatus;
use App\Modules\BusinessDetail\Models\BusinessValidation;
use App\Modules\User\Models\User;
class Business extends Model
{
      protected $table = 'business_basics';

    protected $fillable = [
        'user_id','business_name','business_type','industry','date_of_incorporation'
    ];
    // protected $casts = [
    //     'date_of_incorporation' => 'date:Y-m-d'
    // ];
       public function business()
    {
        return $this->belongsTo(Business::class, 'user_id', user_id);
    }
    
     public function user()
    {
        return $this->belongsTo(User::class, 'user_id', 'id');
    }

    // public function contact()
    // {
    //     return $this->hasOne(BusinessContact::class, 'user_id', 'id');
    // }

    // public function address()
    // {
    //     return $this->hasOne(BusinessAddress::class, 'user_id', 'id');
    // }

    // public function bank()
    // {
    //     return $this->hasOne(BusinessBank::class, 'user_id', 'id');
    // }

    // public function identification()
    // {
    //     return $this->hasOne(BusinessIdentification::class, 'user_id', 'id');
    // }

    // public function documents()
    // {
    //     return $this->hasOne(BusinessDocuments::class, 'user_id', 'id');
    // }

    // public function kyc()
    // {
    //     return $this->hasOne(BusinessKyc::class, 'user_id', 'id');
    // }

    // public function status()
    // {
    //     return $this->hasOne(BusinessStatus::class, 'user_id', 'id');
    // }
}
