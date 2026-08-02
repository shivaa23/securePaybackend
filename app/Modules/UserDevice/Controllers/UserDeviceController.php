<?php

namespace App\Modules\UserDevice\Controllers;

use Laravel\Lumen\Routing\Controller as BaseController;
use App\Traits\ApiResponser;
use Illuminate\Http\Request;
use App\Modules\UserDevice\Models\UserDeviceValidation;
use Auth;
use App\Modules\UserDevice\Models\UserDevice;
use App\Helper\ResponseHelper;

class UserDeviceController extends BaseController
{
    use ApiResponser;

    public function __construct()
    {
        $this->middleware('auth', ['except' => []]);
        $this->middleware('check.active', ['except' => []]);
        $this->user = Auth::user();
    }
    
   public function getUserDevice()
{
    $user = Auth::user();

    if ($user->role == 'adm') {
        $userDevices = UserDevice::orderBy('created_at', 'desc')->get(); // Latest first
        return ResponseHelper::success('User Devices fetched', $userDevices, 200);
    } else {
        $userDevices = UserDevice::where('user_id', $user->id)
                                 ->orderBy('created_at', 'desc')
                                 ->get();
        return ResponseHelper::success('User Devices fetched', $userDevices, 200);
    }
}

}