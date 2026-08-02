<?php

namespace App\Modules\Cms\Controllers;

use Laravel\Lumen\Routing\Controller as BaseController;
use App\Traits\ApiResponser;
use Illuminate\Http\Request;
use App\Modules\Cms\Models\CmsValidations;
use Auth;
use App\Helper\ResponseHelper;
use Carbon\Carbon;

class CmsController extends BaseController
{
    use ApiResponser;

    public function __construct()
    {
        $this->middleware('auth', ['except' => ['createAccount', 'updateAccount', 'encryptPayload']]);
        $this->middleware('check.active', ['except' => '']);
        $this->middleware('check.service.time',['except' => '']);
    }

    public function createOrderCms(Request $request)
    {
  
        $validator = CmsValidations::validatePf($request->all());
        if ($validator->fails()) {
            return ResponseHelper::error('Validation Failed', $validator->errors(), 400);
        }

        $user = Auth::user();
        if ($user->fingId == null) {
            return ResponseHelper::error('Unauthorised Access', '', 403);
        }

        // Non-web flow
        if ($request->pf != 'WEB') {
            $order_id = Carbon::now()->timestamp . rand(1000, 9999);
            $postData = [
                'merchantId'      => $user->fingId,
                'secretkey'       => env('secretkeyCMS'),
                'superMerchantId' => '1412',
                'refId'           => $order_id,
            ];

            return ResponseHelper::success('Order created successfully', $postData, 200);
        }
        $loginrequestdata = [
            'additionalParams'   => null,
            'latitude'           => $request->latitude,
            'loginType'          => '2',
            'longitude'          => $request->longitude,
            'supermerchantId'    => '1412',
            'merchantId'         => $user->fingId,
            'merchantPin'        => md5(1234),
            // 'mobileNumber'       => $request->mobile,
            // 'amount'             => $request->amount,
            'superMerchantSkey'  => env('secretkeyCMS'),
        ];

        $jsonData       = json_encode($loginrequestdata);
        $parsedBase64Key = base64_decode(env('secretkeyCMS'));

        $iv = openssl_random_pseudo_bytes(openssl_cipher_iv_length('aes-256-cbc'));
        $encryptedData = openssl_encrypt(
            $jsonData,
            'aes-256-cbc',
            $parsedBase64Key,
            0,
            $iv
        );
        $encryptedDataWithIV = base64_encode($iv . $encryptedData);

        $postData = [
            'encryptInfo' => $encryptedDataWithIV,
            'secretKey'   => env('secretkeyCMS'),
        ];

        return ResponseHelper::success('Order created successfully', [
            'requestData'  => $loginrequestdata,
            'encryptedKey' => $encryptedDataWithIV,
        ], 200);
    }
   public function createOrderMatm(Request $request)
    {
        $user = Auth::user();
        if ($user->fingId == null) {
            return response()->json(
                [
                    'status' => 'FAILED',
                    'message' => 'Unauthorised Access',
                ],
                403
            );
        }
        $order_id = Carbon::now()->timestamp . rand(1000, 9999);
        $postData = [
            'merchantId' => $user->fingId,
            'password' => '81dc9bdb52d04dc20036dbd8313ed055',
            'superMerchantId' => '1412',
            'secretkey'       => env('secretkeyCMS'),
            'refId' => $order_id,
        ];
        return response()->json(
            [
                'status' => 'SUCCESS',
                'data' => $postData,
            ],
            200
        );
    }
}
