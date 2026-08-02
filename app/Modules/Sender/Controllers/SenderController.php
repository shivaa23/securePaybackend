<?php

namespace App\Modules\Sender\Controllers;

use Laravel\Lumen\Routing\Controller as BaseController;
use App\Traits\ApiResponser;
use Illuminate\Http\Request;
use App\Modules\Payout\Models\Sender;
use App\Modules\Payout\Models\SenderValidation;
use Auth;
use App\Helper\ResponseHelper;

class SenderController extends BaseController
{
    use ApiResponser;

    public function __construct()
    {
        $this->middleware('auth', ['except' => ['']]);
        $this->middleware('check.active', ['except' => '']);

    }
    
    public function getSenders(){
        $user = Auth::user();
        if (!in_array($user->role, ["ret", "dd", "api"])) {
            return ResponseHelper::error('Unauthorised Action', '', 403);
        }
        $senders = Sender::where('mobile_number',$request->mobile_number)->where('pipe',$request->pipe)->first();
        if(!$sender){
            return ResponseHelper::success('Sender Not Found', '', 200);
        }
        if($sender->is_active == 0){
            return ResponseHelper::success('Sender Deactivated', '', 200);
        }
        if($sender->kyc_status == 'pending'){
            return ResponseHelper::success('Sender Not verified', '', 200);
        }
        
        
        return ResponseHelper::success('payout Txns fetched', $senders, 200);
    }
    
    public function getSenders(){
        $user = Auth::user();
        if (!in_array($user->role, ["sadm", "adm"])) {
            return ResponseHelper::error('Unauthorised Action', '', 403);
        }
        $senders = Sender::paginate(10);
        return ResponseHelper::success('payout Txns fetched', $senders, 200);
    }
}
