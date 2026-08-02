<?php

namespace App\Modules\Kyc\Controllers;

use Laravel\Lumen\Routing\Controller as BaseController;
use App\Traits\ApiResponser;
use Illuminate\Http\Request;
use App\Modules\Kyc\Models\Kyc;
use App\Modules\Kyc\Models\KycValidation;
use Auth;
use Illuminate\Support\Facades\Crypt;
use App\Helper\ResponseHelper;

class KycController extends BaseController
{
    use ApiResponser;

    public function __construct()
    {
        $this->middleware('auth', ['except' => '']);
        $this->middleware('check.active', ['except' => '']);
    }
    
    public function getKycs()
    {
        if(Auth::user()->role != "sadm"){
             return ResponseHelper::error('Action not Authorised', '', 403);
        }
        $Kycs = Kyc::all();
        if($Kycs){
            return ResponseHelper::success('Kycs fetched', $Kycs, 200);
        }else{
            return ResponseHelper::error('No Data Found', [], 200);

        }
    }
    
    public function createKyc(Request $request)
    {
       	$validator = KycValidation::createKyc($request->all());
        if ($validator->fails()) {
            return ResponseHelper::error('Validation Failed', $validator->errors(), 422);
        }
   
        $Kyc=new Kyc();
        $Kyc->user_id=$request->user_id;
        $Kyc->pan = Crypt::encryptString($request->pan);
        $Kyc->aadhar = Crypt::encryptString($request->aadhar);
        $Kyc->file_url="hello world!";
        $saveData=$Kyc->save();
        if($saveData){
            return ResponseHelper::success('Kyc Created Successfully', $Kyc, 200);
        }else{
            return ResponseHelper::error('Something Went Wrong', '', 403);

        }
    }
    
    public function updateKyc(Request $request)
    {
       	$validator = KycValidation::updateKyc($request->all());
        if ($validator->fails()) {
            return ResponseHelper::error('Validation Failed', $validator->errors(),'', 422);
        }
        if(Auth::user()->role!='sadm'){
            return ResponseHelper::error('Action not Authorised', '', 403);
        }
        $Kyc=Kyc::find($request->id);
        if($Kyc){
            $Kyc->pan = Crypt::encryptString($request->pan);
            $Kyc->aadhar = Crypt::encryptString($request->aadhar);
            $Kyc->status=$request->status??$Kyc->status;
            $Kyc->user_id=$request->user_id??$Kyc->user_id;
            if($Kyc->save()){
                return ResponseHelper::success('Kyc updated Successfully', $Kyc, 200);
            }
        }else{
            return ResponseHelper::error('Something Went Wrong', '', 403);

        }
    }
}
