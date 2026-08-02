<?php
    namespace App\Modules\Template\Models;
use Illuminate\Database\Eloquent\Model;
use Validator;

class TemplateValidation extends Model
{
    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
	//
    ];
    
    public static function createTemplate($request){
        return Validator::make($request, [
            'name'    => 'required|string|unique:templates,name',
            'vendor' => 'required|string',
            'message' => 'required|string|unique:templates,message',
            'temp_id' => 'sometimes|string|unique:templates,temp_id',
        ]);
	}
	
	public static function updateTemplate($request){
        return Validator::make($request, [
            'id' => 'required|integer',
            'name' => 'sometimes|string|unique:templates,name',
            'vendor' => 'sometimes|string',
            'message' => 'sometimes|string|unique:templates,message',
            'temp_id' => 'sometimes|string|unique:templates,temp_id',
        ]);
	}
	   public static function deleteTemplate($request)
    {
        return Validator::make($request, [
            'id' => 'required|integer|exists:templates,id',
        ]);
    }
    
}