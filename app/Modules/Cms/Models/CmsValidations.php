<?php

namespace App\Modules\Cms\Models;

use Validator;

class CmsValidations
{
    public static function validatePf($request)
    {
        return Validator::make($request, [
            'pf' => 'required|string|in:WEB,APP',
             'latitude'  => 'required',
            'longitude' => 'required',
            // 'mobile'    => 'required',
            // 'amount'    => 'required|numeric',
        ]);
    }

    // public static function validateWeb($request)
    // {
    //     return Validator::make($request, [
    //         'latitude'  => 'required',
    //         'longitude' => 'required',
    //         'mobile'    => 'required',
    //         'amount'    => 'required|numeric',
    //     ]);
    // }
}
