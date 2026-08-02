<?php

namespace App\Modules\Integration\Models;

use Illuminate\Database\Eloquent\Model;

class Abhayam extends Model
{
    public static function service($data)
    {
        $headers = [
            "Content-Type: application/json",
            "Accept: application/json"
        ];

        $curl = curl_init();

        curl_setopt_array($curl, [
            CURLOPT_URL            => $data['url'],
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_POSTFIELDS     => $data['payload'], 
            CURLOPT_CUSTOMREQUEST  => $data['method'] ?? 'POST',
            CURLOPT_HTTPHEADER     => $headers,
            CURLOPT_TIMEOUT        => 30,
        ]);

        $response = curl_exec($curl);

        curl_close($curl);

        return json_decode($response, true);
    }
}
