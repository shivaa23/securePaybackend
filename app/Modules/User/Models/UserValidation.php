<?php
namespace App\Modules\User\Models;
use Illuminate\Database\Eloquent\Model;
use Validator;
class UserValidation extends Model
{
    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
	//
    ];
public static function activeInactive($request)
{
    return Validator::make($request, [
        'id' => 'required|integer|exists:users,id',
        'is_active' => 'required|integer|in:0,1',
        'mpin' => 'required|digits:6'
    ], [
        'id.required'   => 'User ID is required.',
        'id.integer'    => 'User ID must be an integer.',
        'id.exists'     => 'User not found.',

        'is_active.required' => 'Active status is required.',
        'is_active.integer'  => 'Active status must be an integer (0 or 1).',
        'is_active.in'       => 'Active status must be either 0 (inactive) or 1 (active).',

        'mpin.required' => 'MPIN is required.',
        'mpin.digits'   => 'MPIN must be exactly 6 digits.',
    ]);
}
public static function changeLayout($request)
{
    return Validator::make($request, [
  
        'is_layout' => 'required|integer|in:1,2',
        'mpin' => 'required|digits:6'
    ], [

        'is_layout.required' => 'Active status is required.',
        'is_layout.integer'  => 'Active status must be an integer (0 or 1).',
        'is_layout.in'       => 'Active status must be either 0 (inactive) or 1 (active).',

        'mpin.required' => 'MPIN is required.',
        'mpin.digits'   => 'MPIN must be exactly 6 digits.',
    ]);
}

}

  
