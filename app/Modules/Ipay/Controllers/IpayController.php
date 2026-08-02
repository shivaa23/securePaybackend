<?php

namespace App\Modules\Ipay\Controllers;

use Laravel\Lumen\Routing\Controller as BaseController;
use Illuminate\Http\Request;
use App\Modules\Ipay\Models\Ipay;
use App\Modules\Ipay\Models\IpayValidation;
use Illuminate\Support\Facades\Auth;
use App\Helper\ResponseHelper;

class IpayController extends BaseController
{
    public function __construct()
    {
        $this->middleware('auth');
        $this->middleware('check.active');
    }

    public function getIpay(Request $request)
    {
        if (!in_array(Auth::user()->role, ['sadm', 'adm'])) {
            return ResponseHelper::error('Action not Authorised', '', 403);
        }

        $query = Ipay::query();

        if ($request->filled('user_id')) {
            $query->where('user_id', $request->user_id);
        }
           if ($request->filled('instid')) {
            $query->where('instid', $request->instid);
        }


         $paginate = $request->input('paginate', 15);
          $ipay = $query->latest()->paginate($paginate);
        return $ipay->isNotEmpty()
            ? ResponseHelper::success('Ipay Details fetched', $ipay, 200)
            : ResponseHelper::success('No Data Found', [], 200);
    }

    public function updateIpay(Request $request)
    {
        $validator = IpayValidation::updateIpay($request->all());
        if ($validator->fails()) {
            return ResponseHelper::error('Validation Failed', $validator->errors(), 422);
        }

        if (!in_array(Auth::user()->role, ['sadm', 'adm'])) {
            return ResponseHelper::error('Action not Authorised', '', 403);
        }

        $ipay = Ipay::find($request->id);

        if (!$ipay) {
            return ResponseHelper::error('Record Not Found', '', 404);
        }

        $ipay->update([
            'latitude'  => $request->latitude,
            'longitude' => $request->longitude,
            'instid' =>$request->instid,
            'ipay_id' =>$request->instid
        ]);

        return ResponseHelper::success(
            'Latitude & Longitude updated successfully',
            $ipay,
            200
        );
    }
}
