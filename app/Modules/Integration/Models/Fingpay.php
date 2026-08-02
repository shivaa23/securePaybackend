<?php
namespace App\Modules\Integration\Models;

use Illuminate\Database\Eloquent\Model;
use Auth;

define('CLIENT_ID','YWY3OTAzYzNlM2ExZTJlOUNgiNPRSs+j3bM3yYFEBhk=');
define('CLIENT_SECRET','916391dbd6437964f98b7c9ac40a5a6d835a1e26bc292defd23f653789f3e017');

class Fingpay extends Model
{
    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [

    ];

	public static function fingPay($data){

		$post = json_decode($data['post']);
// 		return $data['post']; // Request String
		$dc = (isset($data['deviceId'])) ? $data['deviceId'] :'14234534634565375';
		/* Rendom kye for encryption */
        $key = '';
        $mt_rand = array(0, 1, 2, 3, 4, 5, 6, 7, 8, 9, 10, 11, 12, 13, 14, 15);
        foreach ($mt_rand as $chr) {
            $key .= chr($chr);
        }

		$iv =   '06f2f04cc530364f';
        $ciphertext_raw = openssl_encrypt(json_encode($post), 'AES-128-CBC', $key, $options=OPENSSL_RAW_DATA, $iv);
        $request = base64_encode($ciphertext_raw);
       $filePath = base_path('fingpay_public_production.cer'); // Lumen me base_path()
       $fp = fopen($filePath, 'r');
        $pub_key_string = fread($fp,8192);
		//return $pub_key_string;
        fclose($fp);
        openssl_public_encrypt($key,$crypttext,$pub_key_string);

        $header = [
        'Content-Type: text/xml',
        'trnTimestamp:'.date('d/m/Y H:i:s'),
        'hash:'.base64_encode(hash("sha256",json_encode($post), True)),
        'deviceIMEI:'.$dc,
        'eskey:'.base64_encode($crypttext)
        ];

        $curl = curl_init();
        curl_setopt_array($curl, array(
        CURLOPT_URL => $data['url'],
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_SSL_VERIFYPEER => true,
        CURLOPT_SSL_VERIFYHOST => 2,
        CURLOPT_TIMEOUT => 30,
        CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
        CURLOPT_CUSTOMREQUEST => $data['method'],
        CURLOPT_POSTFIELDS => $request,
        CURLOPT_HTTPHEADER => $header
        ));
        $result = curl_exec($curl);
        $array = json_decode($result, true);
        return $array;
	}

    public static function threeWayReconFp($data){
        $myData = json_decode($data['post'],true);
		$post = array($myData); // Request String
        $post = json_encode($post);
        $string = $post.'saimoneyd7545305d0073ed330d7acd600654e54c0a849545b64d25ff4228935ad3d43d86';
        $hash = base64_encode(hash("sha256",$string, true));
        $curl = curl_init();

        curl_setopt_array($curl, array(
        CURLOPT_URL => $data['url'],
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_ENCODING => '',
        CURLOPT_MAXREDIRS => 10,
        CURLOPT_TIMEOUT => 0,
        CURLOPT_FOLLOWLOCATION => true,
        CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
        CURLOPT_CUSTOMREQUEST => 'POST',
        CURLOPT_POSTFIELDS =>$post,
        CURLOPT_HTTPHEADER => array(
            'txnDate:'.$data['txnDate'],
            'superMerchantLoginId: saimoneyd',
            'superMerchantid: 1412',
            'hash: '.$hash,
            'Content-Type: text/plain'
        ),
        ));

        $response = curl_exec($curl);

        curl_close($curl);
        $array = json_decode($response, true);
        return $array;
	}
}
