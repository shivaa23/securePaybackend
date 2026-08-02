<?php

namespace App\Modules\Payout\Controllers;

use Laravel\Lumen\Routing\Controller as BaseController;
use App\Modules\Payout\Models\PayoutBeneficiary;
use App\Modules\Payout\Models\BeneficiaryValidation;
use App\Traits\ApiResponser;
use Illuminate\Http\Request;
use Auth;
use App\Helper\ResponseHelper;

class BeneficiaryController extends BaseController
{
    use ApiResponser;
    
    public function __construct()
    {
        $this->middleware('auth', ['except' => []]);
    } 
    

    public function getBeneficiaries()
    {   
        $user = Auth::user();
        $beneficiaries = PayoutBeneficiary::orderBy('id', 'desc')->get(); 
        return ResponseHelper::success('Beneficiaries fetched successfully', $beneficiaries, 200);
    }

    public function getBeneficiarySchema(Request $request)
    {   
        $user = Auth::user();
     
        $schema = BeneficiaryValidation::getBeneficiarySchema($request); 
    
        return ResponseHelper::success('Beneficiaries  schema fetched successfully', $schema, 200);
    }
    public function getBeneficiarySchemaUpi(Request $request)
    {   
        $user = Auth::user();
     
        $schema = BeneficiaryValidation::getBeneficiarySchemaUpi(); 
    
        return ResponseHelper::success('Beneficiaries  schema fetched successfully', $schema, 200);
    }

    // public function createBeneficiary(Request $request)
    // {
    //     $validator = BeneficiaryValidation::createBeneficiary($request->all());
    //     if ($validator->fails()) {
    //         return ResponseHelper::error("Validation error", $validator->errors(), 422);
    //     }
    //     // $beneficiaryName = preg_replace('/[^A-Za-z0-9\s]/', '', $request->beneficiary_name);

    //     $beneficiary = new PayoutBeneficiary();
    //     $beneficiary->sender_id        = $request->sender_id;
    //     $beneficiary->beneficiary_name = $request->beneficiary_name;
    //     $beneficiary->account_number   = $request->account_number;
    //     $beneficiary->ifsc_code        = $request->ifsc_code;
    //     $beneficiary->type        = $request->type;
    //     $beneficiary->bank_name        = $request->bank_name;
    //     $beneficiary->mobile_number    = $request->mobile_number;
    //     $beneficiary->is_verified    = $request->is_verified??0;

    //     if ($beneficiary->save()) {
    //         return ResponseHelper::success('Beneficiary created successfully', $beneficiary, 200);
    //     } else {
    //         return ResponseHelper::error('Beneficiary could not be created', [], 403);
    //     }
    // }
    public function createBeneficiary(Request $request)
    {
        $validator = BeneficiaryValidation::createBeneficiary($request->all());
        if ($validator->fails()) {
            return ResponseHelper::error("Validation error", $validator->errors(), 422);
        }
        $name = preg_replace('/[^A-Za-z\s]/', ' ', $request->beneficiary_name);
        $name = preg_replace('/\s+/', ' ', $name);
        $beneficiaryName = ucwords(trim($name));
        $beneficiary = new PayoutBeneficiary();
        $beneficiary->sender_id        = $request->sender_id;
        $beneficiary->beneficiary_name = $beneficiaryName;
        $beneficiary->account_number   = $request->account_number;
        $beneficiary->ifsc_code        = $request->ifsc_code;
        $beneficiary->type             = $request->type;
        $beneficiary->bank_name        = $request->bank_name;
        $beneficiary->mobile_number    = $request->mobile_number;
        $beneficiary->is_verified      = $request->is_verified ?? 0;
    
        if ($beneficiary->save()) {
            return ResponseHelper::success('Beneficiary created successfully', $beneficiary, 200);
        } else {
            return ResponseHelper::error('Beneficiary could not be created', [], 403);
        }
    }


    public function updateBeneficiary(Request $request)
    {
        $validator = BeneficiaryValidation::updateBeneficiary($request->all());
        if ($validator->fails()) {
            return ResponseHelper::error('Validation failed', $validator->errors(), 422);
        }

        $beneficiary = PayoutBeneficiary::find($request->id);
        if (!$beneficiary) {
            return ResponseHelper::error('Beneficiary not found', [], 404);
        }

        $beneficiary->sender_id        = $request->sender_id        ?? $beneficiary->sender_id;
        $beneficiary->beneficiary_name = $request->beneficiary_name ?? $beneficiary->beneficiary_name;
        $beneficiary->account_number   = $request->account_number   ?? $beneficiary->account_number;
        $beneficiary->ifsc_code        = $request->ifsc_code        ?? $beneficiary->ifsc_code;
        $beneficiary->bank_name        = $request->bank_name        ?? $beneficiary->bank_name;
        $beneficiary->mobile_number    = $request->mobile_number    ?? $beneficiary->mobile_number;
        $beneficiary->is_verified      = $request->is_verified      ?? $beneficiary->is_verified;
        $beneficiary->is_active        = $request->is_active        ?? $beneficiary->is_active;

        if ($beneficiary->save()) {
            return ResponseHelper::success('Beneficiary updated successfully', $beneficiary, 200);
        } else {
            return ResponseHelper::error('Beneficiary could not be updated', [], 403);
        }
    }

  public function deleteBeneficiary(Request $request)
{
    $validator = BeneficiaryValidation::deleteBeneficiary($request->all());
    if ($validator->fails()) {
        return ResponseHelper::error('Validation failed', $validator->errors(), 422);
    }
  
$beneficiary = PayoutBeneficiary::where("sender_id", $request->sender_id)
    ->where("id", $request->id)
    ->first();


    if (!$beneficiary) {
        return ResponseHelper::error('Beneficiary not found', [], 404);
    }

    if ($beneficiary->delete()) {
        return ResponseHelper::success('Beneficiary deleted successfully', [], 200);
    } else {
        return ResponseHelper::error('Beneficiary could not be deleted', [], 403);
    }
}

}
