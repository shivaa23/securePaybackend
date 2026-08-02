<?php

namespace App\Modules\LayoutModule\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Validator;
use App\Modules\LayoutModule\Models\LayoutModuleValidation;
class LayoutModuleValidation extends Model
{
    public static function createLayoutModule($request)
    {
        return Validator::make($request->all(), [
            'name' => 'required|string|max:255',
            'url'  => 'required|string|max:255',
            'role' => 'required|string|in:adm,ret',
        ]);
    }
public static function updateFundRequest($request)
{
    return Validator::make($request, [
        'id'     => 'required|exists:fund_requests,id',
        'status' => 'required|in:approved,rejected',
    ]);
}


    // Function to return validation schema for frontend


}
