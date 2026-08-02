<?php

namespace App\Modules\Risk\Controllers;

use Laravel\Lumen\Routing\Controller as BaseController;
use Illuminate\Http\Request;
use App\Helper\ResponseHelper;
use App\Modules\Risk\Models\WhiteListedAcc;
use App\Modules\Risk\Models\BlackListedAcc;
use App\Modules\Risk\Models\RiskValidation;
use Auth;
class RiskController extends BaseController
{
    // ---------------- White-listed Accounts ----------------

    public function createWhiteAccount(Request $request)
    {
        $user=Auth::user();
        $validator = RiskValidation::whiteList($request->all());
        if ($validator->fails()) {
            return ResponseHelper::error('Validation Failed', $validator->errors(), 422);
        }

        $whiteAcc = new WhiteListedAcc();
        $whiteAcc->acc_no     = $request->acc_no;
        $whiteAcc->acc_name   = $request->acc_name;
        $whiteAcc->acc_type   = $request->acc_type;
        $whiteAcc->handled_by = $user->name;
        $whiteAcc->limit      = $request->limit;
        $whiteAcc->save();

        return ResponseHelper::success('White-listed account created', $whiteAcc);
    }

    public function updateWhiteAccount(Request $request)
    {
        $user=Auth::user();
        $id = $request->id;
        if (!$id) return ResponseHelper::error('ID is required', [], 422);

        $whiteAcc = WhiteListedAcc::find($id);
        if (!$whiteAcc) return ResponseHelper::error('Account not found', [], 404);

       $validator = RiskValidation::whiteList($request->all(), $id);
        if ($validator->fails()) {
            return ResponseHelper::error('Validation Failed', $validator->errors(), 422);
        }

        $whiteAcc->acc_no     = $request->acc_no;
        $whiteAcc->acc_name   = $request->acc_name;
        $whiteAcc->acc_type   = $request->acc_type;
        $whiteAcc->handled_by = $user->name;
        $whiteAcc->limit      = $request->limit;
        $whiteAcc->save();

        return ResponseHelper::success('White-listed account updated', $whiteAcc);
    }

    public function deleteWhiteAccount(Request $request)
    {
        $id = $request->id;
        if (!$id) return ResponseHelper::error('ID is required', [], 422);

        $whiteAcc = WhiteListedAcc::find($id);
        if (!$whiteAcc) return ResponseHelper::error('Account not found', [], 404);

        $whiteAcc->delete();
        return ResponseHelper::success('White-listed account deleted');
    }

    public function getWhiteAccount(Request $request)
    {
        $whiteAcc = WhiteListedAcc::all();
        if (!$whiteAcc) return ResponseHelper::error('Account not found', [], 403);

        return ResponseHelper::success('White-listed account fetched', $whiteAcc);
    }

    // ---------------- Black-listed Accounts ----------------

    public function createBlackAccount(Request $request)
    {
        $user=Auth::user();
        $validator = RiskValidation::blackList($request->all());
        if ($validator->fails()) {
            return ResponseHelper::error('Validation Failed', $validator->errors(), 422);
        }

        $blackAcc = new BlackListedAcc();
        $blackAcc->acc_no     = $request->acc_no;
        $blackAcc->handled_by = $user->name;
        $blackAcc->save();

        return ResponseHelper::success('Black-listed account created', $blackAcc);
    }

    public function updateBlackAccount(Request $request)
    {
        $user=Auth::user();
        $id = $request->id;
        if (!$id) return ResponseHelper::error('ID is required', [], 422);

        $blackAcc = BlackListedAcc::find($id);
        if (!$blackAcc) return ResponseHelper::error('Account not found', [], 404);

         $validator = RiskValidation::blackList($request->all(), $id);
        if ($validator->fails()) {
            return ResponseHelper::error('Validation Failed', $validator->errors(), 422);
        }

        $blackAcc->acc_no     = $request->acc_no;
        $blackAcc->handled_by = $user->name;
        $blackAcc->save();

        return ResponseHelper::success('Black-listed account updated', $blackAcc);
    }

    public function deleteBlackAccount(Request $request)
    {
        $id = $request->id;
        if (!$id) return ResponseHelper::error('ID is required', [], 422);

        $blackAcc = BlackListedAcc::find($id);
        if (!$blackAcc) return ResponseHelper::error('Account not found', [], 404);

        $blackAcc->delete();
        return ResponseHelper::success('Black-listed account deleted');
    }

    public function getBlackAccount(Request $request)
    {
      
        $blackAcc = BlackListedAcc::all();
        if (!$blackAcc) return ResponseHelper::error('Account not found', [], 404);

        return ResponseHelper::success('Black-listed account fetched', $blackAcc);
    }
}
