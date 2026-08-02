<?php

namespace App\Modules\Irctc\Controllers;

use Laravel\Lumen\Routing\Controller as BaseController;
use App\Traits\ApiResponser;
use Illuminate\Http\Request;
use App\Modules\Irctc\Models\IrctcTxn;
use App\Modules\Irctc\Models\IrctcValidation;
use Auth;
use App\Helper\ResponseHelper;

class IrctcController extends BaseController
{
    use ApiResponser;

    public function __construct()
    {
        $this->middleware('auth', ['except' => ['']]);
        $this->middleware('check.active', ['except' => '']);

    }
    
    public function getIrctcTxns()
    // {
    //           if(Auth::user()->role != "sadm"&&Auth::user()->role != "ret"){
    //          return ResponseHelper::error('Action not Authorised', '', 403);
    //     }
    //     $irctctxns = IrctcTxn::all();
    //     if($irctctxns){
    //         return ResponseHelper::success('Irctc Txns fetched',$irctctxns, 200);
    //     }else{
    //         return ResponseHelper::success('No Data Found', [], 200);

    //     }
    // }
    {
    $user = Auth::user();

    if (in_array($user->role, ["sadm", "adm"])) {
        $irctctxns = IrctcTxn::all();
        return ResponseHelper::success('Irctc Txns fetched', $irctctxns, 200);
    }

    $irctctxns = IrctcTxn::where("user_id", $user->id)->get();
    return ResponseHelper::success('Irctc Txns fetched', $irctctxns, 200);
}

}
