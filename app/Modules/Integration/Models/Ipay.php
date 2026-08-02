<?php
namespace App\Modules\Integration\Models;

use Illuminate\Database\Eloquent\Model;
use Auth;

class Ipay extends Model
{
    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [

    ];

	public static function service($data){
		//$instId = Auth::user()->instId;
		//return $data['payload'];
		$user=Auth::user();
		$instId = $user->instId??476746;
// 		return $data['payload'];
         if(!empty($data['DUMMY'])){
        $response = '{"statuscode":"TXN","actcode":null,"status":"Transaction Successful","data":{"externalRef":"ACV1758023475309","poolReferenceId":"1250916172116SFBTH","txnValue":"1.00","txnReferenceId":"525979570763","pool":{"account":"9999442202","openingBal":"6811063.61","mode":"DR","amount":"2.18","closingBal":"6811061.43"},"payer":{"account":"9999442202","name":"MOBIFAST SOLUTIONS PRIVATE LIMITED"},"payee":{"name":"MOBIFAST SOLUTIONS PVT LTD","account":"37296396227","ifsc":"SBIN0000001","type":"","mccCode":"","merchantOnboardingType":"","merchantGenre":"","accountType":"","iin":"","nameMatchPercent":45.75999999999999801048033987171947956085205078125,"origName":"UNKNOWN"},"isCached":false,"isPennyDrop":true},"timestamp":"2025-09-16 17:21:17","ipay_uuid":"h0009fe42c7a-a2d3-45bb-9f46-e8aebb766d4a-scFbD57BaOOB","orderid":null,"environment":"LIVE","internalCode":null}';
        return json_decode($response, true);
        }
        $headers = [
            'X-Ipay-Auth-Code: 1',
            'X-Ipay-Client-Id: ' . env('IPAY_CLIENTID'),
            'X-Ipay-Client-Secret: ' . env('IPAY_SECRET'),
            'X-Ipay-Endpoint-Ip: ' . $data['ip'],
            'X-Ipay-Outlet-Id: ' . $instId,
            'Accept: application/json',
            'Content-Type: application/json',
        ];
        // return $headers;
    
        $curl = curl_init($data['url']);
        curl_setopt_array($curl, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_TIMEOUT => 0,
            CURLOPT_CUSTOMREQUEST => $data['method'],
            CURLOPT_POSTFIELDS => $data['payload'],
            CURLOPT_HTTPHEADER => $headers,
        ]);
    
        $response = curl_exec($curl);
        curl_close($curl);
    
        return json_decode($response, true);
	}
		public static function Aeps($data){
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
		    'X-Ipay-Auth-Code:  1',
		    'X-Ipay-Client-Id: '.env('IPAY_CLIENTID'),
		    'X-Ipay-Client-Secret: '.env('IPAY_SECRET'),
		    'X-Ipay-Endpoint-Ip: '.$data['ip'],
		    'Accept: application/json',
		    'Content-Type: application/json'
		  ),
		));

		$response = curl_exec($curl);

		curl_close($curl);
		return json_decode($response,true);
	}
	public static function bbpsApi($data){

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
		    'X-Ipay-Auth-Code:  1',
		    'X-Ipay-Client-Id: '.CLIENT_ID,
		    'X-Ipay-Client-Secret: '.CLIENT_SECRET,
		    'X-Ipay-Endpoint-Ip: '.$data['ip'],
			'X-Ipay-Outlet-Id: '.$data['outlet_id'],
		    'Accept: application/json',
		    'Content-Type: application/json'
		  ),
		));

		$response = curl_exec($curl);

		curl_close($curl);
		return json_decode($response,true);
	}
	public static function dmr($data){
		
		$instId = $data['outlet_id'];
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
		    'X-Ipay-Auth-Code:  1',
		    'X-Ipay-Client-Id: '.CLIENT_ID,
		    'X-Ipay-Client-Secret: '.CLIENT_SECRET,
		    'X-Ipay-Endpoint-Ip: '.$data['ip'],
			'X-Ipay-Outlet-Id: '.$instId,
		    'Accept: application/json',
		    'Content-Type: application/json'
		  ),
		));

		$response = curl_exec($curl);

		curl_close($curl);
		return json_decode($response,true);
	}

	public static function payOut($data){
		
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
		    'X-Ipay-Auth-Code:  1',
		    'X-Ipay-Client-Id: '.CLIENT_ID,
		    'X-Ipay-Client-Secret: '.CLIENT_SECRET,
		    'X-Ipay-Endpoint-Ip: '.$data['ip'],
		    'Accept: application/json',
		    'Content-Type: application/json'
		  ),
		));

		$response = curl_exec($curl);

		curl_close($curl);
		return json_decode($response,true);
	}



	// public static function initiateSignup($data){
	// 	$curl = curl_init();

	// 	curl_setopt_array($curl, array(
	// 	  CURLOPT_URL => 'https://api.instantpay.in/user/outlet/signup/initiate',
	// 	  CURLOPT_RETURNTRANSFER => true,
	// 	  CURLOPT_ENCODING => '',
	// 	  CURLOPT_MAXREDIRS => 10,
	// 	  CURLOPT_TIMEOUT => 0,
	// 	  CURLOPT_FOLLOWLOCATION => true,
	// 	  CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
	// 	  CURLOPT_CUSTOMREQUEST => 'POST',
	// 	  CURLOPT_POSTFIELDS =>'{
	// 			    "mobile": "'.$data['mobile'].'",
	// 			    "pan": "'.$data['pan'].'",
	// 			    "email": "'.$data['email'].'",
	// 			    "aadhaar": "'.$data['aadhaar'].'",
	// 			    "latitude": "'.$data['latitude'].'",
	// 			    "longitude": "'.$data['longitude'].'",
	// 			    "consent": "Y"
	// 			}',
	// 	  CURLOPT_HTTPHEADER => array(
	// 	    'X-Ipay-Auth-Code:  1',
	// 	    'X-Ipay-Client-Id: YWY3OTAzYzNlM2ExZTJlOUNgiNPRSs+j3bM3yYFEBhk=',
	// 	    'X-Ipay-Client-Secret: 916391dbd6437964f98b7c9ac40a5a6d835a1e26bc292defd23f653789f3e017',
	// 	    'X-Ipay-Endpoint-Ip: '.$data['ip'],
	// 	    'Accept: application/json',
	// 	    'Content-Type: application/json'
	// 	  ),
	// 	));

	// 	$response = curl_exec($curl);

	// 	curl_close($curl);
	// 	return json_decode($response,true);
	// }

	public static function validateSignup($data){
		$curl = curl_init();

		curl_setopt_array($curl, array(
		  CURLOPT_URL => 'https://api.instantpay.in/user/outlet/signup/validate',
		  CURLOPT_RETURNTRANSFER => true,
		  CURLOPT_ENCODING => '',
		  CURLOPT_MAXREDIRS => 10,
		  CURLOPT_TIMEOUT => 0,
		  CURLOPT_FOLLOWLOCATION => true,
		  CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
		  CURLOPT_CUSTOMREQUEST => 'POST',
		  CURLOPT_POSTFIELDS =>'{
				  "otpReferenceID": "'.$data['otpReferenceID'].'",
				  "otp" : "'.$data['otp'].'",
				  "hash": "'.$data['hash'].'"
				}',
		  CURLOPT_HTTPHEADER => array(
		    'X-Ipay-Auth-Code:  1',
		    'X-Ipay-Client-Id: YWY3OTAzYzNlM2ExZTJlOUNgiNPRSs+j3bM3yYFEBhk=',
		    'X-Ipay-Client-Secret: 916391dbd6437964f98b7c9ac40a5a6d835a1e26bc292defd23f653789f3e017',
		    'X-Ipay-Endpoint-Ip: '.$data['ip'],
		    'Accept: application/json',
		    'Content-Type: application/json'
		  ),
		));

		$response = curl_exec($curl);

		curl_close($curl);
		return json_decode($response,true);
	}

	public static function cashWithdrawl($data){
		$instId = $data['outlet_id'];
		$aadhaarNumber = $data['AadharNumber'];
		//return $aadhaarNumber;
        $encryptionKey = 'b55961e2b5bf1441b55961e2b5bf1441';
        $ivlen = openssl_cipher_iv_length('aes-256-cbc');
        $iv = openssl_random_pseudo_bytes($ivlen);
        $ciphertext = openssl_encrypt($aadhaarNumber,'aes-256-cbc', $encryptionKey, OPENSSL_RAW_DATA, $iv);
        $encryptedData = base64_encode($iv . $ciphertext);
		//return $encryptedData;
		// $location = explode(',', $data['location']);
		$curl = curl_init();

		curl_setopt_array($curl, array(
		  CURLOPT_URL => 'https://api.instantpay.in/fi/aeps/cashWithdrawal',
		  CURLOPT_RETURNTRANSFER => true,
		  CURLOPT_ENCODING => '',
		  CURLOPT_MAXREDIRS => 10,
		  CURLOPT_TIMEOUT => 0,
		  CURLOPT_FOLLOWLOCATION => true,
		  CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
		  CURLOPT_CUSTOMREQUEST => 'POST',
		  CURLOPT_POSTFIELDS =>'{
				"bankiin": "'.$data['bank_iin'].'",
				"latitude": "'.$data['latitude'].'",
				"longitude": "'.$data['longitude'].'",
				"mobile": "'.$data['number'].'",
				"amount": "'.$data['amount'].'",
				"externalRef": "'.$data['order_id'].'",
				"biometricData": {
					"encryptedAadhaar": "'.$encryptedData.'",
					"dc": "'.$data['dc'].'",
					"ci": "'.$data['ci'].'",
					"hmac": "'.$data['hmac'].'",
					"dpId": "'.$data['dpId'].'",
					"mc": "'.$data['mc'].'",
					"pidDataType": "'.$data['pidDataType'].'",
					"sessionKey": "'.$data['sessionKey'].'",
					"mi": "'.$data['mi'].'",
					"rdsId": "'.$data['rdsId'].'",
					"errCode": "0",
					"errInfo": "Success",
					"fCount": "'.$data['fCount'].'",
					"fType": "2",
					"iCount": 0,
					"iType": "",
					"pCount": 0,
					"pType": "",
					"srno": "'.$data['srno'].'",
					"sysid": "",
					"ts": "'.$data['created_at'].'",
					"pidData": "'.$data['pidData'].'",
					"qScore": "'.$data['qScore'].'",
					"nmPoints": "'.$data['nmPoints'].'",
					"rdsVer": "'.$data['rdsVer'].'"
				}
			}',
		  CURLOPT_HTTPHEADER => array(
		    'X-Ipay-Auth-Code:  1',
			'X-Ipay-Outlet-Id: '.$instId,
		    'X-Ipay-Client-Id: '.CLIENT_ID,
		    'X-Ipay-Client-Secret: '.CLIENT_SECRET,
		    'X-Ipay-Endpoint-Ip: '.$data['ip'],
		    'Accept: application/json',
		    'Content-Type: application/json'
		  ),
		));

		$response = curl_exec($curl);

		curl_close($curl);
		return json_decode($response,true);
	}

	public static function balanceInquiry($data){
		$instId = $data['outlet_id'];
		$aadhaarNumber = $data['AadharNumber'];
		//return $aadhaarNumber;
        $encryptionKey = 'b55961e2b5bf1441b55961e2b5bf1441';
        $ivlen = openssl_cipher_iv_length('aes-256-cbc');
        $iv = openssl_random_pseudo_bytes($ivlen);
        $ciphertext = openssl_encrypt($aadhaarNumber,'aes-256-cbc', $encryptionKey, OPENSSL_RAW_DATA, $iv);
        $encryptedData = base64_encode($iv . $ciphertext);
		//return $encryptedData;
		// $location = explode(',', $data['location']);
		$curl = curl_init();

		curl_setopt_array($curl, array(
		  CURLOPT_URL => 'https://api.instantpay.in/fi/aeps/balanceInquiry',
		  CURLOPT_RETURNTRANSFER => true,
		  CURLOPT_ENCODING => '',
		  CURLOPT_MAXREDIRS => 10,
		  CURLOPT_TIMEOUT => 0,
		  CURLOPT_FOLLOWLOCATION => true,
		  CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
		  CURLOPT_CUSTOMREQUEST => 'POST',
		  CURLOPT_POSTFIELDS =>'{
				"bankiin": "'.$data['bank_iin'].'",
				"latitude": "'.$data['latitude'].'",
				"longitude": "'.$data['longitude'].'",
				"mobile": "'.$data['number'].'",
				"amount": "100",
				"externalRef": "'.$data['order_id'].'",
				"biometricData": {
					"encryptedAadhaar": "'.$encryptedData.'",
					"dc": "'.$data['dc'].'",
					"ci": "'.$data['ci'].'",
					"hmac": "'.$data['hmac'].'",
					"dpId": "'.$data['dpId'].'",
					"mc": "'.$data['mc'].'",
					"pidDataType": "'.$data['pidDataType'].'",
					"sessionKey": "'.$data['sessionKey'].'",
					"mi": "'.$data['mi'].'",
					"rdsId": "'.$data['rdsId'].'",
					"errCode": "0",
					"errInfo": "Success",
					"fCount": "'.$data['fCount'].'",
					"fType": "2",
					"iCount": 0,
					"iType": "",
					"pCount": 0,
					"pType": "",
					"srno": "'.$data['srno'].'",
					"sysid": "",
					"ts": "'.$data['created_at'].'",
					"pidData": "'.$data['pidData'].'",
					"qScore": "'.$data['qScore'].'",
					"nmPoints": "'.$data['nmPoints'].'",
					"rdsVer": "'.$data['rdsVer'].'"
				}
			}',
		  CURLOPT_HTTPHEADER => array(
		    'X-Ipay-Auth-Code:  1',
			'X-Ipay-Outlet-Id: '.$instId,
		    'X-Ipay-Client-Id: '.CLIENT_ID,
		    'X-Ipay-Client-Secret: '.CLIENT_SECRET,
		    'X-Ipay-Endpoint-Ip: '.$data['ip'],
		    'Accept: application/json',
		    'Content-Type: application/json'
		  ),
		));

		$response = curl_exec($curl);

		curl_close($curl);
		return json_decode($response,true);
	}

	public static function miniStatement($data){
		$instId = $data['outlet_id'];
		$aadhaarNumber = $data['AadharNumber'];
		//return $aadhaarNumber;
        $encryptionKey = 'b55961e2b5bf1441b55961e2b5bf1441';
        $ivlen = openssl_cipher_iv_length('aes-256-cbc');
        $iv = openssl_random_pseudo_bytes($ivlen);
        $ciphertext = openssl_encrypt($aadhaarNumber,'aes-256-cbc', $encryptionKey, OPENSSL_RAW_DATA, $iv);
        $encryptedData = base64_encode($iv . $ciphertext);
		//return $encryptedData;
		// $location = explode(',', $data['location']);
		$curl = curl_init();

		curl_setopt_array($curl, array(
		  CURLOPT_URL => 'https://api.instantpay.in/fi/aeps/miniStatement',
		  CURLOPT_RETURNTRANSFER => true,
		  CURLOPT_ENCODING => '',
		  CURLOPT_MAXREDIRS => 10,
		  CURLOPT_TIMEOUT => 0,
		  CURLOPT_FOLLOWLOCATION => true,
		  CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
		  CURLOPT_CUSTOMREQUEST => 'POST',
		  CURLOPT_POSTFIELDS =>'{
				"bankiin": "'.$data['bank_iin'].'",
				"latitude": "'.$data['latitude'].'",
				"longitude": "'.$data['longitude'].'",
				"mobile": "'.$data['number'].'",
				"amount": "100",
				"externalRef": "'.$data['order_id'].'",
				"biometricData": {
					"encryptedAadhaar": "'.$encryptedData.'",
					"dc": "'.$data['dc'].'",
					"ci": "'.$data['ci'].'",
					"hmac": "'.$data['hmac'].'",
					"dpId": "'.$data['dpId'].'",
					"mc": "'.$data['mc'].'",
					"pidDataType": "'.$data['pidDataType'].'",
					"sessionKey": "'.$data['sessionKey'].'",
					"mi": "'.$data['mi'].'",
					"rdsId": "'.$data['rdsId'].'",
					"errCode": "0",
					"errInfo": "Success",
					"fCount": "'.$data['fCount'].'",
					"fType": "2",
					"iCount": 0,
					"iType": "",
					"pCount": 0,
					"pType": "",
					"srno": "'.$data['srno'].'",
					"sysid": "",
					"ts": "'.$data['created_at'].'",
					"pidData": "'.$data['pidData'].'",
					"qScore": "'.$data['qScore'].'",
					"nmPoints": "'.$data['nmPoints'].'",
					"rdsVer": "'.$data['rdsVer'].'"
				}
			}',
		  CURLOPT_HTTPHEADER => array(
		    'X-Ipay-Auth-Code:  1',
			'X-Ipay-Outlet-Id: '.$instId,
		    'X-Ipay-Client-Id: '.CLIENT_ID,
		    'X-Ipay-Client-Secret: '.CLIENT_SECRET,
		    'X-Ipay-Endpoint-Ip: '.$data['ip'],
		    'Accept: application/json',
		    'Content-Type: application/json'
		  ),
		));

		$response = curl_exec($curl);

		curl_close($curl);
		return json_decode($response,true);
	}

	public static function AadharPay($data){
		$instId = $data['outlet_id'];
		$aadhaarNumber = $data['AadharNumber'];
		//return $aadhaarNumber;
        $encryptionKey = 'b55961e2b5bf1441b55961e2b5bf1441';
        $ivlen = openssl_cipher_iv_length('aes-256-cbc');
        $iv = openssl_random_pseudo_bytes($ivlen);
        $ciphertext = openssl_encrypt($aadhaarNumber,'aes-256-cbc', $encryptionKey, OPENSSL_RAW_DATA, $iv);
        $encryptedData = base64_encode($iv . $ciphertext);
		//return $encryptedData;
		// $location = explode(',', $data['location']);
		$curl = curl_init();

		curl_setopt_array($curl, array(
		  CURLOPT_URL => 'https://api.instantpay.in/fi/aeps/aadhaarPay',
		  CURLOPT_RETURNTRANSFER => true,
		  CURLOPT_ENCODING => '',
		  CURLOPT_MAXREDIRS => 10,
		  CURLOPT_TIMEOUT => 0,
		  CURLOPT_FOLLOWLOCATION => true,
		  CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
		  CURLOPT_CUSTOMREQUEST => 'POST',
		  CURLOPT_POSTFIELDS =>'{
				"bankiin": "'.$data['bank_iin'].'",
				"latitude": "'.$data['latitude'].'",
				"longitude": "'.$data['longitude'].'",
				"mobile": "'.$data['number'].'",
				"amount": "'.$data['amount'].'",
				"externalRef": "'.$data['order_id'].'",
				"biometricData": {
					"encryptedAadhaar": "'.$encryptedData.'",
					"dc": "'.$data['dc'].'",
					"ci": "'.$data['ci'].'",
					"hmac": "'.$data['hmac'].'",
					"dpId": "'.$data['dpId'].'",
					"mc": "'.$data['mc'].'",
					"pidDataType": "'.$data['pidDataType'].'",
					"sessionKey": "'.$data['sessionKey'].'",
					"mi": "'.$data['mi'].'",
					"rdsId": "'.$data['rdsId'].'",
					"errCode": "0",
					"errInfo": "Success",
					"fCount": "'.$data['fCount'].'",
					"fType": "2",
					"iCount": 0,
					"iType": "",
					"pCount": 0,
					"pType": "",
					"srno": "'.$data['srno'].'",
					"sysid": "",
					"ts": "'.$data['created_at'].'",
					"pidData": "'.$data['pidData'].'",
					"qScore": "'.$data['qScore'].'",
					"nmPoints": "'.$data['nmPoints'].'",
					"rdsVer": "'.$data['rdsVer'].'"
				}
			}',
		  CURLOPT_HTTPHEADER => array(
		    'X-Ipay-Auth-Code:  1',
			'X-Ipay-Outlet-Id: '.$instId,
		    'X-Ipay-Client-Id: '.CLIENT_ID,
		    'X-Ipay-Client-Secret: '.CLIENT_SECRET,
		    'X-Ipay-Endpoint-Ip: '.$data['ip'],
		    'Accept: application/json',
		    'Content-Type: application/json'
		  ),
		));

		$response = curl_exec($curl);

		curl_close($curl);
		return json_decode($response,true);
	}

	public static function billPaymentCc($data){
		$location = explode(',', $data['location']);
		$curl = curl_init();

		curl_setopt_array($curl, array(
		  CURLOPT_URL => 'https://api.instantpay.in/payments/payout',
		  CURLOPT_RETURNTRANSFER => true,
		  CURLOPT_ENCODING => '',
		  CURLOPT_MAXREDIRS => 10,
		  CURLOPT_TIMEOUT => 0,
		  CURLOPT_FOLLOWLOCATION => true,
		  CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
		  CURLOPT_CUSTOMREQUEST => 'POST',
		  CURLOPT_POSTFIELDS =>'{
		    "payer" : {
		        "bankId" : "0",
		        "bankProfileId" : "0",
		        "accountNumber" : "7982090261"
		    },
		    "payee" : {
		        "name" : "'.$data->ben_name.'",
		        "accountNumber" : "'.$data->ben_acc.'"
		    },
		    "transferMode" : "CREDITCARD",
		    "transferAmount" : "'.$data->amount.'",
		    "externalRef" : "'.$data->order_id.'",
		    "latitude" : "'.$location[0].'",
		    "longitude" : "'.$location[1].'",
		    "remarks" : "Credit Card Bill",
		    "alertEmail" : "paisaonmobile@gmail.in"
		}',
		  CURLOPT_HTTPHEADER => array(
		    'X-Ipay-Auth-Code:  1',
		    'X-Ipay-Client-Id: YWY3OTAzYzNlM2ExZTJlOUNgiNPRSs+j3bM3yYFEBhk=',
		    //'X-Ipay-Client-Secret: dc97c24b0dcad847539c1ab3a4e85963eb27e86ae83e8aa690c616e2fc1fb793',//uat
		    'X-Ipay-Client-Secret: 916391dbd6437964f98b7c9ac40a5a6d835a1e26bc292defd23f653789f3e017',//live
		    'X-Ipay-Endpoint-Ip: '.$data->ip,
		    'Accept: application/json',
		    'Content-Type: application/json'
		  ),
		));

		$response = curl_exec($curl);

		curl_close($curl);
		return json_decode($response,true);
	}

	
	public static function loadWallet($data){
		$curl = curl_init();

		curl_setopt_array($curl, array(
		  CURLOPT_URL => 'https://api.instantpay.in/payments/payout',
		  CURLOPT_RETURNTRANSFER => true,
		  CURLOPT_ENCODING => '',
		  CURLOPT_MAXREDIRS => 10,
		  CURLOPT_TIMEOUT => 0,
		  CURLOPT_FOLLOWLOCATION => true,
		  CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
		  CURLOPT_CUSTOMREQUEST => 'POST',
		  CURLOPT_POSTFIELDS =>'{
		    "payer" : {
		        "bankId" : "0",
		        "bankProfileId" : "0",
		        "accountNumber" : "7982090261"
		    },
		    "payee" : {
		        "name" : "'.$data->ben_name.'",
		        "accountNumber" : "'.$data->ben_acc.'"
		    },
		    "transferMode" : "'.$data->ifsc.'",
		    "transferAmount" : "'.$data->amount.'",
		    "externalRef" : "'.$data->order_id.'",
		    "latitude" : "'.$data->latitude.'",
		    "longitude" : "'.$data->longitude.'",
		    "remarks" : "PREPROD",
		    "alertEmail" : "paisaonmobile@gmail.in",
    		"purpose": "OTHERS"
		}',
		  CURLOPT_HTTPHEADER => array(
		    'X-Ipay-Auth-Code:  1',
		    'X-Ipay-Client-Id: YWY3OTAzYzNlM2ExZTJlOUNgiNPRSs+j3bM3yYFEBhk=',
		    //'X-Ipay-Client-Secret: dc97c24b0dcad847539c1ab3a4e85963eb27e86ae83e8aa690c616e2fc1fb793',//uat
		    'X-Ipay-Client-Secret: 916391dbd6437964f98b7c9ac40a5a6d835a1e26bc292defd23f653789f3e017',//live
		    'X-Ipay-Endpoint-Ip: '.$data->ip,
		    'Accept: application/json',
		    'Content-Type: application/json'
		  ),
		));

		$response = curl_exec($curl);

		curl_close($curl);
		return json_decode($response,true);
	}

	public static function okyc($data){
		
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
		    'X-Ipay-Auth-Code:  1',
		    'X-Ipay-Client-Id: '.CLIENT_ID,
		    'X-Ipay-Client-Secret: '.CLIENT_SECRET,
		    'X-Ipay-Endpoint-Ip: '.$data['ip'],
		    'Accept: application/json',
		    'Content-Type: application/json'
		  ),
		));

		$response = curl_exec($curl);

		curl_close($curl);
		return json_decode($response,true);
	}

	// public static function txnStatus($data){
	// 	$date = $data['transaction_date'];
	// 	$id = $data['external_id'];
	// 	$curl = curl_init();

	// 	curl_setopt_array($curl, array(
	// 	  CURLOPT_URL => 'https://api.instantpay.in/reports/txnStatus',
	// 	  CURLOPT_RETURNTRANSFER => true,
	// 	  CURLOPT_ENCODING => '',
	// 	  CURLOPT_MAXREDIRS => 10,
	// 	  CURLOPT_TIMEOUT => 0,
	// 	  CURLOPT_FOLLOWLOCATION => true,
	// 	  CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
	// 	  CURLOPT_CUSTOMREQUEST => 'POST',
	// 	  CURLOPT_POSTFIELDS =>'{
	// 				"transactionDate" : "'.$data['transaction_date'].'",
	// 				"externalRef"     : "'.$data['external_id'].'",
	// 				"source"          : "ORDER"
	// 			}',
	// 	  CURLOPT_HTTPHEADER => array(
	// 	    'X-Ipay-Auth-Code:  1',
	// 	    'X-Ipay-Client-Id: YWY3OTAzYzNlM2ExZTJlOUNgiNPRSs+j3bM3yYFEBhk=',
	// 	    //'X-Ipay-Client-Secret: dc97c24b0dcad847539c1ab3a4e85963eb27e86ae83e8aa690c616e2fc1fb793',//uat
	// 	    'X-Ipay-Client-Secret: 916391dbd6437964f98b7c9ac40a5a6d835a1e26bc292defd23f653789f3e017',//live
	// 	    'X-Ipay-Endpoint-Ip: '.$data->ip,
	// 	    'Accept: application/json',
	// 	    'Content-Type: application/json'
	// 	  ),
	// 	));

	// 	$response = curl_exec($curl);

	// 	curl_close($curl);
	// 	return json_decode($response,true);
	// }
	
	
	
	    public static function fetchOutlets($payload, $clientIp)
    {

        $curl = curl_init();

        curl_setopt_array($curl, [
            CURLOPT_URL => "https://api.instantpay.in/user/outlet/list",
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_POST => true,
            CURLOPT_POSTFIELDS => json_encode($payload),
            CURLOPT_HTTPHEADER => [
                'X-Ipay-Auth-Code:  1',
    		    'X-Ipay-Client-Id: '.CLIENT_ID,
    		    'X-Ipay-Client-Secret: '.CLIENT_SECRET,
                "X-Ipay-Endpoint-Ip: {$clientIp}",
                "Content-Type: application/json",
                'Accept: application/json',
            ]
        ]);


		$response = curl_exec($curl);

		curl_close($curl);
		return json_decode($response,true);

        
    }

}




