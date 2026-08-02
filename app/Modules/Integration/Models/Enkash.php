<?php

namespace App\Modules\Integration\Models;

use Illuminate\Database\Eloquent\Model;

class Enkash extends Model
{
    public static function generateToken()
    {
        $curl = curl_init();
        curl_setopt_array($curl, [
            CURLOPT_URL => 'https://api.enkash.in/oauth/token',
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_ENCODING => '',
            CURLOPT_TIMEOUT => 0,
            CURLOPT_CUSTOMREQUEST => 'POST',
            CURLOPT_POSTFIELDS => 'username=9974606468&grant_type=password&password=Puspa@124212&clientId=CEKPEIRQ0V',
            CURLOPT_HTTPHEADER => [
                'Content-Type: application/x-www-form-urlencoded',
                'Authorization: Basic Q0VLUEVJUlEwVjo1NjhkMzdjYTk3NDdmODVhZjNjZDI5OGY2MjFmZGQwNDE4OTNmZDJj'
            ],
        ]);

        $response = curl_exec($curl);
        curl_close($curl);

        if (!$response) {
            return null;
        }

        $data = json_decode($response, true);
        return $data['access_token'] ?? null;
    }

    public static function service($data)
    {
        $token = self::generateToken();
        if (!$token) {
            return [
                'statuscode' => 'ERR',
                'message'    => 'Failed to generate access token',
                'data'       => []
            ];
        }
    
        $curl = curl_init();
        curl_setopt_array($curl, [
            CURLOPT_URL => 'https://api.enkash.in/cron/api/v0/pennyDrop',
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT => 0,
            CURLOPT_CUSTOMREQUEST => 'POST',
            CURLOPT_POSTFIELDS => json_encode([
                "beneficiaryAccountNumber" => $data['beneficiaryAccount'],
                "customerRequestId"        => $data['client_id'],
                "beneficiaryIfscCode"      => $data['ifscCode'],
                "pennyLess"                => false
            ]),
            CURLOPT_HTTPHEADER => [
                'Content-Type: application/json',
                'Authorization: ' . 'Bearer ' . $token
            ],
        ]);
    
        $response = curl_exec($curl);
        curl_close($curl);
    
        // if (!$response) {
        //     return [
        //         'statuscode' => 'ERR',
        //         'message'    => 'No response from Enkash API',
        //         'data'       => []
        //     ];
        // }
    
        $decoded = json_decode($response, true);
        if (isset($decoded['response_code']) && $decoded['response_code'] == 0) {
            return [
                'statuscode' => 'TXN',
                'message'    => $decoded['response_message'] ,
                'data'       => [
                    'txnReferenceId'  => $decoded['payload']['utr'] ,
                    'beneficiaryName' => $decoded['payload']['beneficiaryName'] ,
                    'status'          => $decoded['payload']['status'] ,
                    'response'=>$decoded
                ]
            ];
        } else {
            return [
                'statuscode' => 'ERR',
                'message'    => $decoded['response_message'] ?? 'Verification Failed',
                'data'       => [
                    'error' => $decoded['payload'] ?? 'Failed'
                ]
            ];
        }
    }
public static function apiBal2()
{
    $token = self::generateToken();
    if (!$token) {
        return [
            'statuscode' => 'ERR',
            'message'    => 'Failed to generate access token',
            'data'       => []
        ];
    }

    $url = 'https://api.enkash.in/cron/api/v0/payout-account/balance';

    $curl = curl_init();
    curl_setopt_array($curl, [
        CURLOPT_URL => $url,
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_TIMEOUT => 0,
        CURLOPT_HTTPGET => true, 
        CURLOPT_HTTPHEADER => [
            'Content-Type: application/json',
            'Authorization: Bearer ' . $token,
        ],
    ]);

    $response = curl_exec($curl);
    $err = curl_error($curl);
    curl_close($curl);

    if ($err) {
        return [
            'statuscode' => 'ERR',
            'message'    => 'cURL Error: ' . $err,
            'data'       => []
        ];
    }

    if (!$response) {
        return [
            'statuscode' => 'ERR',
            'message'    => 'No response from Enkash API',
            'data'       => []
        ];
    }

    return json_decode($response, true);
}
public static function apiBal()
{
    $tokenData = self::generateToken();
        if (!$tokenData) {
            return [
                'statuscode' => 'ERR',
                'message'    => 'Failed to generate access token',
                'data'       => []
            ];
        }
    $token = $tokenData; // Use your live token
    $companyId = 'CEKPEIRQ0V';
    $cardAccountId = 'CATSKLJ';
    $url = "https://pay.enkash.in/api/v0/cardAccount?companyId={$companyId}&cardAccountId={$cardAccountId}";
//   return $url;
    $curl = curl_init();

    curl_setopt_array($curl, [
        CURLOPT_URL => $url,
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_ENCODING => '',
        CURLOPT_MAXREDIRS => 10,
        CURLOPT_TIMEOUT => 30,
        CURLOPT_FOLLOWLOCATION => true,
        CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
        CURLOPT_CUSTOMREQUEST => 'GET',
        CURLOPT_HTTPHEADER => [
            'accept: application/json',
            'Authorization: Bearer ' . $token,
            'User-Agent: Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36',
            // Add Cookie header exactly as in your request
            'Cookie: JSESSIONID=9DE70C8E4222152A5CE80F48F3AD9F4C; _cfuvid=GJO6eL5PS6LSCs2_fj9yFDXz_jGKTjBrp92heClr1Q8-1765278347.5261784-1.0.1.1-opvQ1av9QEN9acfbtdj1x.t3eP1TwY8ZLo39qmO60j4; _cfuvid=zHvwjTRX52m95lwSwqdu27Z.msgZFcBaa4uaREkGhXM-1765279111.0205994-1.0.1.1-D0Qokck1xwB5fp62uKVoNGysbMgiVROVt3Ku1dTqa.U'
        ],
    ]);

    $response = curl_exec($curl);
    // return $response;
    if (curl_errno($curl)) {
        $error = curl_error($curl);
        curl_close($curl);
        return [
            'statuscode' => 'ERR',
            'message'    => 'cURL Error: ' . $error,
            'data'       => []
        ];
    }

    curl_close($curl);

    $decoded = json_decode($response, true);
  return $decoded;
    if (isset($decoded['balance'])) { // Adjust this according to actual API response
        return [
            'statuscode' => 'TXN',
            'message'    => 'Balance fetched successfully',
            'balance'    => $decoded['balance']
        ];
    }

    return [
        'statuscode' => 'ERR',
        'message'    => 'Invalid response from Enkash API',
        'data'       => $decoded
    ];
}







}
