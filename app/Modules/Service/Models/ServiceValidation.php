<?php
    namespace App\Modules\Service\Models;
use Illuminate\Database\Eloquent\Model;
use Validator;

class ServiceValidation extends Model
{
    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
	//
    ];
    
    public static function createService($request){
        return Validator::make($request, [
            'code' => 'required|string|unique:services',
            'name' => 'required|string',
            'route' => 'sometimes|string',
            'category' => 'sometimes|string',
            
        ]);
	}
	
	public static function updateService($request){
        return Validator::make($request, [
            'id' => 'required|integer',
            'code' => 'sometimes|string',
            'name' => 'sometimes|string',
            'is_active' => 'sometimes|integer',
            'route' => 'sometimes|string',
            'category' => 'sometimes|string',
            
        ]);
	}
    public static function blockUnblock($request)
    {
        return Validator::make($request, [
            'id'        => 'required|integer|exists:services,id',
            'is_active' => 'sometimes|integer|in:0,1',
            'is_active_api' => 'sometimes|integer|in:0,1',
            'is_active_users' => 'sometimes|integer|in:0,1',
        ]);
    }

    
}