<?php

namespace App\Modules\User\Models;

use Illuminate\Auth\Authenticatable;
use Illuminate\Contracts\Auth\Access\Authorizable as AuthorizableContract;
use Illuminate\Contracts\Auth\Authenticatable as AuthenticatableContract;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Laravel\Lumen\Auth\Authorizable;
use Illuminate\Support\Facades\Crypt;
use App\Modules\User\Models\Permission;
use App\Modules\BusinessDetail\Models\Business;

use App\Modules\BusinessDetail\Models\BusinessAddress;
class User extends Model implements AuthenticatableContract, AuthorizableContract
{
    use Authenticatable, Authorizable, HasFactory;

    protected $fillable = [
        'name',
        'email',
        'password',
        "pan",
        'api_token',
        'w1',
        'w2',
        'w3',
        'lien',
        'mobile',
        'vpa',
        'mpin',
        'parent',
        'establishment'
    ];

    protected $hidden = [
        'password', 'api_token', 'mpin'
    ];
    
    public function permissions(){
        return $this->hasOne(Permission::class);
    }
    
    public function business(){
        return $this->hasOne(Business::class, 'user_id', 'id');
    }

    /**
     * Accessor for w1
     */
    public function getW1Attribute($value){
        try {
            return (float) Crypt::decryptString($value);
        } catch (\Exception $e) {
            return 0;
        }
    }

    /**
     * Mutator for w1
     */
    public function setW1Attribute($value){
        $this->attributes['w1'] = Crypt::encryptString((string) $value);
    }

    /**
     * Accessor for w2
     */
    public function getW2Attribute($value){
        try {
            return (float) Crypt::decryptString($value);
        } catch (\Exception $e) {
            return 0;
        }
    }

    /**
     * Mutator for w2
     */
    public function setW2Attribute($value){
        $this->attributes['w2'] = Crypt::encryptString((string) $value);
    }
    
    public function getW3Attribute($value){
        try {
            return (float) Crypt::decryptString($value);
        } catch (\Exception $e) {
            return 0;
        }
    }

    /**
     * Mutator for w2
     */
    public function setW3Attribute($value){
        $this->attributes['w3'] = Crypt::encryptString((string) $value);
    }
    
    public function incrementW1(float $amount): void
    {
        $this->w1 = $this->w1 + $amount;
        $this->save();
    }
    
    public function decrementW1(float $amount): void
    {
        $this->w1 = $this->w1 - $amount; // Allow negative values
        $this->save();
    }

    public function setLienAttribute($value){
        $this->attributes['lien'] = Crypt::encryptString((string) $value);
    }

    // Accessor for 'lien' (decrypt on get)
    public function getLienAttribute($value){
        try {
            return (float) Crypt::decryptString($value);
        } catch (\Exception $e) {
            return 0;
        }
    }

    // Increment 'lien' value
    // In User.php
    public function incrementLien(float $amount): void
    {
        $this->lien = ($this->lien ?? 0) + $amount; // Ensure null-safe addition
        $this->save();
    }
    
    public function decrementLien(float $amount): void
    {
        $this->lien = max(0, ($this->lien ?? 0) - $amount); // Prevent negative
        $this->save();
    }
    

      public function incrementW3(float $amount): void
    {
        $this->w3 = $this->w3 + $amount;
        $this->save();
    }

    public function decrementW3(float $amount): void
    {
        $this->w3 = max(0, $this->w3 - $amount);
        $this->save();
    }

    public function incrementW2(float $amount): void
    {
        $this->w2 = $this->w2 + $amount;
        $this->save();
    }

    public function decrementW2(float $amount): void
    {
        $this->w2 = max(0, $this->w2 - $amount);
        $this->save();
    }
   public static function getDownlineUsers($userId, &$visited = [])
    {
        $users = collect();
        if (in_array($userId, $visited)) {
            return $users;
        }
        $visited[] = $userId;

        // Fetch direct children (users who have this user as parent)
        $children = self::where('parent', $userId)->get();

        foreach ($children as $child) {
            $users->push($child);
            $users = $users->merge(self::getDownlineUsers($child->id, $visited));
        }

        return $users;
    }
     public static function getDescendantUserIds($userId)
    {
        $ids = [];

        // Get direct children of this user
        $children = self::where('parent', $userId)->pluck('id')->toArray();

        foreach ($children as $childId) {
            $ids[] = $childId;

            // Recursively get child's descendants
            $ids = array_merge($ids, self::getDescendantUserIds($childId));
        }

        return $ids;
    }
    
          
      public function businessAddresses()
    {
        
        return $this->hasMany(BusinessAddress::class, 'user_id', 'id');
    }  

}
