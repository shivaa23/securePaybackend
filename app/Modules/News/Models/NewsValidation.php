<?php
    namespace App\Modules\News\Models;
use Illuminate\Database\Eloquent\Model;
use Validator;

class NewsValidation extends Model
{
    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
	//
    ];
    
    public static function createNews($request){
        return Validator::make($request, [
            'description' => 'required|string',
           
        ]);
	}
	public static function deleteNews($request)
{
    return Validator::make($request, [
        'id' => 'required|integer|exists:news,id',
    ]);
}

}