<?php
namespace App\Modules\Integration\Models;

use Illuminate\Database\Eloquent\Model;
use Auth;

  use Illuminate\Support\Facades\Log;
class Paypoint extends Model
{
    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [

    ];
    public static function service($data)
    {
        $user = Auth::user();
        $curl = curl_init();
    
        curl_setopt_array($curl, array(
            CURLOPT_URL => $data['url'],
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_ENCODING => '',
            CURLOPT_MAXREDIRS => 10,
            CURLOPT_TIMEOUT => 0,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
            CURLOPT_CUSTOMREQUEST => $data['method'],
            CURLOPT_POSTFIELDS => $data['payload'],
            CURLOPT_HTTPHEADER => array(
                'Accept: application/json',
                'Content-Type: application/json',
                'Authorization: Bearer ' . $token 
            ),
        ));
    
        $response = curl_exec($curl);
        curl_close($curl);
    
        return json_decode($response, true);
    }

    
    public static function serviceQr($data)
    {
        $merchantId   = env('DIGIKHATA_MERCHANT_ID');
        $authKey      = env('DIGIKHATA_AUTH_KEY');
        // return $authKey;
        $interfaceKey = env('DIGIKHATA_INTERFACE_KEY');
        $IdentificationCode = env('DIGIKHATA_IDENTIFICATION_CODE');
    
        $headers = [
            'Accept: application/json',
            'Content-Type: application/json',
            'MerchantID: ' .$merchantId,
            'AuthKey: ' .$authKey,
            'InterfaceKey: ' .$interfaceKey,
        ];
    
        // 🔹 Log Request Headers
        Log::info('DIGIKHATA REQUEST HEADERS', $headers);
    //   return $headers;
        // 🔹 Log Request Payload
        Log::info('DIGIKHATA REQUEST PAYLOAD', [
            'url'     => $data['url'],
            'method'  => $data['method'],
            'payload' => $data['payload'],
        ]);
    
        $curl = curl_init();
    
        curl_setopt_array($curl, [
            CURLOPT_URL            => $data['url'],
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT        => 30,
            CURLOPT_CUSTOMREQUEST  => $data['method'],
            CURLOPT_POSTFIELDS     => $data['payload'],
            CURLOPT_HTTPHEADER     => $headers,
        ]);
    
        $response = curl_exec($curl);
    
        // 🔹 Log Raw Response
        Log::info('DIGIKHATA RESPONSE', [
            'response' => $response
        ]);
    
        if (curl_errno($curl)) {
            Log::error('DIGIKHATA CURL ERROR', [
                'error' => curl_error($curl)
            ]);
        }
    
        curl_close($curl);
       return $response;
        return json_decode($response, true);
    }




    public static function apiBal()
    {
        $user = Auth::user();
        $token = env('PAY2ALL_TOKEN');
    
        $curl = curl_init();
    
        curl_setopt_array($curl, array(
            CURLOPT_URL => 'https://erp.pay2all.in/api/user',
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_ENCODING => '',
            CURLOPT_MAXREDIRS => 10,
            CURLOPT_TIMEOUT => 0,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
            CURLOPT_CUSTOMREQUEST => 'GET',
            CURLOPT_HTTPHEADER => array(
                'Accept: application/json',
                'Content-Type: application/json',
                'Authorization: Bearer ' . $token 
            ),
        ));
    
        $response = curl_exec($curl);
        curl_close($curl);
    
        return json_decode($response, true);
    }

}
