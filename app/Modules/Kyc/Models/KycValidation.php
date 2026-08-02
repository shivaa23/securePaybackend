<?php
    namespace App\Modules\Kyc\Models;
use Illuminate\Database\Eloquent\Model;
use Validator;

class KycValidation extends Model
{
    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
    ];
    
    public static function createKyc($request)
{
    return Validator::make($request, [
        'pan'      => 'required|string|size:11|unique:kycs,pan',  
        'user_id'  => 'required|integer|unique:kycs,user_id',
        'aadhar'   => 'required|digits:12|unique:kycs,aadhar',   
        'file_url' => 'sometimes|url',                                
    ]);
}

	public static function updateKyc($request){
        return Validator::make($request, [
         'pan'     => 'required|string|size:11|unique:kycs,pan',  
        'user_id'  => 'sometimes|integer|unique:kycs,user_id',
        'aadhar'   => 'required|digits:12|unique:kycs,aadhar',   
        'file_url' => 'sometimes|url', 
        'status'   => 'sometimes|integer'
            
        ]);
	}
    
}