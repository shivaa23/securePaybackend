<?php
namespace App\Modules\Integration\Models;

use Illuminate\Database\Eloquent\Model;
use Carbon\Carbon;

class Psprint extends Model
{
    protected $fillable = [];

    public static function service($data)
    {
        if(!empty($data['DUMMY'])){
        $response = '{"status":true,"response_code":1,"message":"Transaction Successful.","ackno":"133774218","referenceid":"DMT21758011417149","utr":"525914000402","txn_status":1,"benename":"MOBIFAST SOLUTIONS PVT LTD","remarks":"Transaction Successful - MOBIFAST SOLUTIONS PVT LTD","remitter":"8851980670","account_number":"37296396227","bc_share":"3.5","txn_amount":"100","NPCI_response_code":"0","bank_status":"Yes","balance":158683.890000000013969838619232177734375,"customercharge":"12.00","gst":"1.83","tds":"0.13","netcommission":"6.54"}';
        return json_decode($response, true);
        }
        $key = env('KEY_PROD');  
        $payload = [
            'timestamp' => Carbon::now()->timestamp,
            'partnerId' => 'PS004357',
            'reqid'     => $data['order_id']
        ];

        $alg = 'HS256';
        $header = ['typ' => 'JWT', 'alg' => $alg];

        // Build JWT
        $segments = [];
        $segments[] = static::urlsafeB64Encode(static::jsonEncode($header));
        $segments[] = static::urlsafeB64Encode(static::jsonEncode($payload));
        $signing_input = implode('.', $segments);

        $signature = static::sign($signing_input, $key, $alg);
        $segments[] = static::urlsafeB64Encode($signature);

        $jwtToken = implode('.', $segments);

        // Call Paysprint API
        $curl = curl_init();
        curl_setopt_array($curl, [
            CURLOPT_URL            => $data['url'],
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_ENCODING       => '',
            CURLOPT_MAXREDIRS      => 10,
            CURLOPT_TIMEOUT        => 0,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_HTTP_VERSION   => CURL_HTTP_VERSION_1_1,
            CURLOPT_CUSTOMREQUEST  => $data['method'],
            CURLOPT_POSTFIELDS     => $data['payload'],
            CURLOPT_HTTPHEADER     => [
                'Token: ' . $jwtToken,
                'Content-Type: application/json',
            ],
        ]);

        $response = curl_exec($curl);
        curl_close($curl);

        return json_decode($response, true);
    }

    protected static function sign($msg, $key, $alg = 'HS256')
    {
        switch ($alg) {
            case 'HS256':
                return hash_hmac('sha256', $msg, $key, true);
            default:
                throw new \DomainException("Algorithm not supported");
        }
    }

    protected static function urlsafeB64Encode($input)
    {
        return rtrim(strtr(base64_encode($input), '+/', '-_'), '=');
    }

    protected static function jsonEncode($input)
    {
        $json = json_encode($input);
        if (json_last_error() !== JSON_ERROR_NONE) {
            throw new \DomainException("JSON encode error: " . json_last_error_msg());
        }
        return $json;
    }
}
