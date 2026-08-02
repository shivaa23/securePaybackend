<?php
namespace App\Modules\Integration\Models;

use Illuminate\Database\Eloquent\Model;

class Soliteck extends Model
{
    private static $authBase = 'https://api-uat.soliteck.in/uat/api/TokenUAT/';

    public static function generateToken()
    {
        $url = 'https://api-uat.soliteck.in/api/Token/GenerateToken';
    
        $headers = [
            'ClientId: ' . env('SOLITECK_CLIENT_ID'),
            'ClientPass: ' . env('SOLITECK_CLIENT_PASS'),
            'ClientTPin: ' . env('SOLITECK_CLIENT_TPIN'),
            'ClientSecret: ' . env('SOLITECK_CLIENT_SECRET'),
            'Accept: application/json',
            'Content-Type: application/json',
        ];
    
        // The ClientReq must be a string (even if empty)
        $postData = json_encode(json_encode([
            'ClientReq' => ''
        ]));
    
        $curl = curl_init($url);
        curl_setopt_array($curl, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_POST => true,
            CURLOPT_POSTFIELDS => $postData,
            CURLOPT_HTTPHEADER => $headers,
            CURLOPT_TIMEOUT => 30,
        ]);
    
        $response = curl_exec($curl);
    
        // if (curl_errno($curl)) {
        //     $error = curl_error($curl);
        //     curl_close($curl);
        //     return ['status' => 'error', 'message' => $error];
        // }
    
        curl_close($curl);
    
        $decoded = json_decode($response, true);
    
        if (isset($decoded['responseCode']) && $decoded['responseCode'] == 200 && !empty($decoded['data'])) {
            return $decoded['data']; 
        }
    
        return ['status' => 'error', 'message' => $decoded['responseMessage'] ?? 'Token not generated'];
    }




    public static function verifyToken($token)
    {
        $url = self::$authBase . 'VerifyUATToken'; // Sometimes Soliteck has a separate verify endpoint

        $payload = json_encode([
            'ClientId' => env('SOLITECK_CLIENT_ID'),
            'Token'    => $token,
        ]);

        $headers = [
            'Accept: application/json',
            'Content-Type: application/json',
        ];

        $curl = curl_init($url);
        curl_setopt_array($curl, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_POST => true,
            CURLOPT_POSTFIELDS => $payload,
            CURLOPT_HTTPHEADER => $headers,
            CURLOPT_TIMEOUT => 30,
        ]);

        $response = curl_exec($curl);
        return $response;
        curl_close($curl);

        $decoded = json_decode($response, true);

        return isset($decoded['responseCode']) && $decoded['responseCode'] == 200;
    }

    public static function service($data)
    {
        $token = self::generateToken();
        // return $token;
        if (!is_string($token)) {
            return $token; 
        }
    //   $verify =self::verifyToken($token);
    //   return $verify;
        // if (!self::verifyToken($token)) {
        //     return ['status' => 'error', 'message' => 'Generated token is invalid'];
        // }

        $headers = [
            'ClientId: ' . env('SOLITECK_CLIENT_ID'),
            'ClientPass: ' . env('SOLITECK_CLIENT_PASS'),
            'ClientTPin: ' . env('SOLITECK_CLIENT_TPIN'),
            'ClientSecret: ' . env('SOLITECK_CLIENT_SECRET'),
            'ClientToken: ' . $token,
            'Accept: application/json',
            'Content-Type: application/json',
        ];

        $curl = curl_init($data['url']);
        curl_setopt_array($curl, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_CUSTOMREQUEST => $data['method'],
            CURLOPT_POSTFIELDS => $data['payload'],
            CURLOPT_HTTPHEADER => $headers,
            CURLOPT_TIMEOUT => 30,
        ]);

        $response = curl_exec($curl);
        curl_close($curl);

        return json_decode($response, true);
    }
}
