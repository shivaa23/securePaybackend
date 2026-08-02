<?php
namespace App\Modules\Role\Models;
use Illuminate\Database\Eloquent\Model;
use Validator;

class RoleValidation extends Model
{
    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
	//
    ];
    
    public static function createRole($request){
        return Validator::make($request, [
            'role' => 'required|string|unique:roles|in:sadm,adm,acc,md,di,ret,dd,asm,zsm,api',
            'description' => 'required|string',
        ]);
	}
	
	public static function updateRole($request){
        return Validator::make($request, [
            'id' => 'required|integer',
            'role' => 'sometimes|string|unique:roles|in:sadm,adm,acc,md,di,ret,dd,asm,zsm,api',
            'description' => 'sometimes|string',
            'status' => 'sometimes|string',

        ]);
	}
    
}