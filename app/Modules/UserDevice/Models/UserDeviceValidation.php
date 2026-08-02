<?php
namespace App\Modules\UserDevice\Models;

use Validator;

class UserDeviceValidation
{
    public static function createUserDevice($request)
    {
        return Validator::make($request, [
            'user_id' => 'required|numeric',
            'ip'      => 'required|string',
            'device'  => 'required|string',

        ]);
    }
}
