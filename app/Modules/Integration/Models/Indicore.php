<?php
namespace App\Modules\Integration\Models;

use Illuminate\Database\Eloquent\Model;

class Indicore extends Model
{
    public static function service($url, $method = 'POST', $payload = [])
    {
        $method = strtoupper($method);
    
        if ($method === 'GET' && !empty($payload)) {
            $url .= '?' . http_build_query($payload);
        }
    
        $curl = curl_init($url);
    
        curl_setopt_array($curl, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_CUSTOMREQUEST  => $method,
            CURLOPT_TIMEOUT        => 30,
        ]);
    
        if ($method === 'POST' && !empty($payload)) {
            curl_setopt($curl, CURLOPT_POSTFIELDS, http_build_query($payload));
        }
    
        $response = curl_exec($curl);
        $error = curl_error($curl);
        curl_close($curl);
    
        return $response;
    }





}
