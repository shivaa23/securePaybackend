<?php

namespace App\Modules\Misc\Models;


use Illuminate\Database\Eloquent\Model;
use Auth;

class Uuid extends Model
{

    /**
     * 
     * The attributes that are mass assignable.
     *
     * @var string[]
     */
    protected $fillable = ['uuid','status','user_id','requestData','action'];

    /**
     * The attributes excluded from the model's JSON form.
     *
     * @var string[]
     */
    protected $hidden = [
        //
    ];
    
    // public static function check($uuid)
    // {
    //     $record = Uuid::where('uuid', $uuid)->first();

    //     if (!$record) {
    //         return ["code" => 202, "message" => "UUID not found"];
    //     }

    //     if ($record->status != 1) {
    //         return ["code" => 202, "message" => "UUID already used or inactive"];
    //     }

    //     $record->update(['status' => 0]);

    //     return ["code" => 200, "message" => "UUID validated and updated"];
    // }
    public static function check($uuid, $action, $request)
    {
        $record = Uuid::where('uuid', $uuid)->first();
    
        if (!$record) {
            return [
                "code"    => 403,
                "message" => "UUID not found"
            ];
        }
    
        if ($record->status !== 1) {
            return [
                "code"    => 403,
                "message" => "UUID already used or inactive"
            ];
        }
    
        $record->update([
            'status'       => 0,
            'requestData'  => is_array($request) ? json_encode($request) : $request,
            'action'       => $action,
        ]);
    
        return [
            "code"    => 200,
            "message" => "UUID validated and updated"
        ];
    }

    
}