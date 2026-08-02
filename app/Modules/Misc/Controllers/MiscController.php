<?php

namespace App\Modules\Misc\Controllers;

use Laravel\Lumen\Routing\Controller as BaseController;
use Illuminate\Support\Facades\Validator;
use Illuminate\Http\Request;
use App\Helper\ResponseHelper;
use App\Modules\User\Models\User;
use App\Modules\Misc\Models\Uuid;
use App\Modules\Integration\Models\Impsguru;
use App\Modules\Integration\Models\Indicore;
use App\Modules\Integration\Models\Soliteck;
use App\Modules\Integration\Models\Enkash;
use Auth;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\Date;
use App\Modules\Integration\Models\Abhayam;
use App\Modules\Integration\Models\Utkal;
class MiscController extends BaseController
{
    

    public function __construct()
    {
        $this->middleware('auth', ['except' => ['logoutAll','chkUuid']]);
        $this->middleware('check.active', ['except' => '']);
    }
  public function apiBalance(Request $request)
{
    // Only allow super admin and admin
    if (!in_array(Auth::user()->role, ['sadm', 'adm'])) {
        return ResponseHelper::error('Action not Authorised', '', 403);
    }

    $data = [
        'IMPSGURU' => 0,
        'INDICORE' => 0,
        'SOLITECK' => 0,
        'ENKASH'  => 0,
        'UTKAL'  => 0,
        'ABHAYAM'  => 0
    ];

    // -------- IMPSGURU BALANCE --------
    $guruUrl = 'https://api.impsguru.com/prod/v1/query/balance';
    $guruBalance = Impsguru::guruRequest($guruUrl, 'POST', '{}', 'APIBAL');

    if ($guruBalance && isset($guruBalance['statuscode']) && $guruBalance['statuscode'] === 'TXN') {
        $data['IMPSGURU'] = floatval($guruBalance['data']['RunningBalance'] ?? 0);
    }

    // -------- ABHAYAM BALANCE --------
    $payload = [
        "Authcode" => env("ABHAYAM_PAY_KEY"),
        "Mpin"     => env("ABHAYAM_PAY_MPIN"),
    ];

    $dataRequest = [
        'url'     => 'https://api.abhayampay.in/Recharge/fetchBalance',
        'payload' => json_encode($payload),
        'method'  => 'POST'
    ];

    $response = Abhayam::service($dataRequest);
    //   return $response;
    if ($response && isset($response['Status']) && $response['Status'] === 'Success') {
    $data['ABHAYAM'] = floatval($response['AvailBalance'] ?? 0);
}

    // -------- UTKAL BALANCE (Corrected) --------
    $payload = [
        "Authcode" => env("UTKAL_PAY_KEY"),
        "Mpin"     => env("UTKAL_PAY_MPIN"),
    ];

    $dataRequest = [
        'url'     => 'https://api.utkaldigital.co.in/Recharge/fetchBalance',
        'payload' => json_encode($payload),
        'method'  => 'POST'
    ];

    $response = Utkal::service($dataRequest);
//   return $response;
  
    if ($response && isset($response['Status']) && $response['Status'] === 'Success') {
        $data['UTKAL'] = floatval($response['AvailBalance'] ?? 0);
    }

    // -------- INDICORE BALANCE --------
    $url = 'https://eabi.indicore.net/';
    $payload = ['signature' => env('INDICORE_SIGNATURE')];
    $indicoreResponse = Indicore::service($url, 'POST', $payload);

    $parts = explode('|', $indicoreResponse);

    $indicore = [
        'statuscode' => $parts[0] ?? null,
        'balance'    => $parts[1] ?? null
    ];

    if ($indicore['statuscode'] === '200') {
        $data['INDICORE'] = floatval($indicore['balance'] ?? 0);
    } elseif ($indicore['statuscode'] === '401') {
        return ResponseHelper::error('Invalid Signature', '', 401);
    }

    // -------- ENKASH BALANCE --------
    $enkash = Enkash::apiBal();

    if ($enkash && isset($enkash['response_code']) && $enkash['response_code'] == 0) {
        $data['ENKASH'] = floatval($enkash['payload']['availableBalance'] ?? 0);
    }

    // -------- SOLITECK BALANCE --------
    $req = "nileshnathalalbujad.11.2083@SoliteckPay";

    $soliteckData = [
        'url'     => 'https://api-uat.soliteck.in/api/Token/APIUserBankDetails',
        'payload' => json_encode($req),
        'method'  => 'POST'
    ];

    $soliteck = Soliteck::service($soliteckData);

    if (isset($soliteck['responseCode']) && $soliteck['responseCode'] == 200) {
        $data['SOLITECK'] = floatval($soliteck['responseData'] ?? 0);
    }

    return ResponseHelper::success('Success', $data, 200);
}



    public function logoutAll(Request $request)
    {
        if ($request->ip() != "103.49.124.232") {
            return ResponseHelper::error('Unauthorized access', [], 401);
        }
        try {
            User::chunk(100, function ($users) {
                foreach ($users as $user) {
                    $user->api_token = Str::random(30);
                    $user->encryption_key = ($user->role == 'api') 
                        ? $user->encryption_key 
                        : Crypt::encrypt(Str::random(1));
                    $user->save();
                }
            });
            return ResponseHelper::success(
                'All users have been logged out successfully',
                [],
                200
            );
        } catch (\Exception $e) {
            return ResponseHelper::error(
                'Failed to logout all users',
                ['error' => $e->getMessage()],
                500
            );
        }
    }
    
    public function chkUuid(Request $request){
        return Uuid::check($request->uuid);
    }
    
    // public function getUuid()
    // {
    //     return Uuid::create([
    //         'uuid' => Str::uuid()->toString(),
    //         'status' => 1,
    //     ]);
    // }
     public function getUuid()
    {
        $user=Auth::user();
        $time = Date::now()->format('ymdHis'); 
        $random = mt_rand(10000, 99999); 
        $uuid = $time . $random;
        $data = Uuid::create([
            'uuid' => $uuid,
            'status' => 1,
            'user_id' =>$user->id,
        ]);

        return response()->json($data);
    }


    
}
