<?php

namespace App\Modules\Integration\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Http;
use Auth;
use App\Modules\Integration\Models\Indicore;
class Recharge extends Model
{
    public static function recharge($data, $service)
    {
        if ($data->route == "WB") {
            return self::wb($data, $service);
        }elseif ($data->route == "SHAKTI") {
            return self::shakti($data, $service);
        }elseif ($data->route == "MROBOTICS") {
            return self::mrb($data, $service);
        }elseif ($data->route == "AONE") {
            return self::aone($data, $service);
        }elseif ($data->route == "IMGURU") {
            return self::guru($data, $service);
        }elseif ($data->route == "INDICORE") {
            return self::indicore($data, $service);
        }

       
    }
    
//     public static function guru($data, $service){
		
// 		$hashData = $data->mobile_number.$data->amount.$service->guru.$data->txn_id.env('GURU_HKEY');
// 		$hash = hash('sha256', $hashData);
// 		if (in_array($service->guru, [1,2,3,4,13])) {
//             $url = 'https://api.impsguru.com/prod/v1/recharge/mobile';
//         }else{
//             $url = 'https://api.impsguru.com/prod/v1/recharge/dth';
//         }
// 		$curl = curl_init();
		
// 		curl_setopt_array($curl, array(
// 		CURLOPT_URL => $url,
// 		CURLOPT_RETURNTRANSFER => true,
// 		CURLOPT_ENCODING => '',
// 		CURLOPT_MAXREDIRS => 10,
// 		CURLOPT_TIMEOUT => 0,
// 		CURLOPT_FOLLOWLOCATION => true,
// 		CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
// 		CURLOPT_CUSTOMREQUEST => 'POST',
// 		CURLOPT_POSTFIELDS =>'{
// 			"mobileNumber":"'.$data->mobile_number.'",
// 			"transferAmount":"'.$data->amount.'",
// 			"operatorCode":"'.$service->guru.'",
// 			"client_id":"'.$data->txn_id.'",
// 			"latitude":"29.1234",
// 			"longitude":"72.1234",
// 			"env":"Live",
// 			"generatedHash":"'.$hash.'"
// 		}',
// 		CURLOPT_HTTPHEADER => array(
// 			'Authorization: Bearer '.env('GURU_TOKEN'),
// 			'Content-Type: application/json'
// 		),
// 		));

// 		$response = curl_exec($curl);

// 		curl_close($curl);
// 		$response = json_decode($response, true);
		
		
// 		$result = [
//         'status'      => 'PENDING',
//         'operator_id' => '',
//         'response'    => $response ?? 'No Response Received',
//     ];
//     $opid = '';
//     if (!empty($response)) {
//         if ($response['statuscode'] === 'TXN') {
//             $opid = $response['data']['txnReferenceId'] ?? '';
//             $result['status'] = 'SUCCESS';
//         }elseif($response['statuscode'] === 'ERR' || $response['statuscode'] === 'VAL') {
//             $result['status'] = 'FAILED';
//         } elseif ($response['statuscode'] === 'TUP') {
//             $result['status'] = 'PENDING';
//         }
//         $result['operator_id'] = $opid;
//         $result['response']    = $response;
//     }

//     return $result;
// 	}
    public static function guru($data, $service){
		$user=Auth::user();
		$hashData = $data->mobile_number.$data->amount.$service->guru.$data->txn_id.env('GURU_HKEY');
		$hash = hash('sha256', $hashData);
		if (in_array($service->guru, [1,2,3,4,13])) {
            $url = 'https://api.impsguru.com/prod/v1/recharge/mobile';
            $payload = '{
    			"mobileNumber":"'.$data->mobile_number.'",
    			"transferAmount":"'.$data->amount.'",
    			"operatorCode":"'.$service->guru.'",
    			"client_id":"'.$data->txn_id.'",
    			"latitude":"29.1234",
    			"longitude":"72.1234",
    			"env":"Live",
    			"generatedHash":"'.$hash.'"
    		}';
        }else{
            $url = 'https://api.impsguru.com/prod/v1/recharge/dth';
            $payload = '{
    			"customerNumber":"'.$data->mobile_number.'",
    			"transferAmount":"'.$data->amount.'",
    			"operatorCode":"'.$service->guru.'",
    			"client_id":"'.$data->txn_id.'",
    			"latitude":"29.1234",
    			"longitude":"72.1234",
    			"env":"Live",
    			"generatedHash":"'.$hash.'"
    		}';
        }
        // return $payload;
    	$curl = curl_init();
	
		curl_setopt_array($curl, array(
		CURLOPT_URL => $url,
		CURLOPT_RETURNTRANSFER => true,
		CURLOPT_ENCODING => '',
		CURLOPT_MAXREDIRS => 10,
		CURLOPT_TIMEOUT => 0,
		CURLOPT_FOLLOWLOCATION => true,
		CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
		CURLOPT_CUSTOMREQUEST => 'POST',
		CURLOPT_POSTFIELDS =>$payload,
		CURLOPT_HTTPHEADER => array(
			'Authorization: Bearer '.env('GURU_TOKEN'),
			'Content-Type: application/json'
		),
		));

		$response = curl_exec($curl);

		curl_close($curl);
		$response = json_decode($response, true);
		
		
		$result = [
        'status'      => 'PENDING',
        'operator_id' => '',
        'response'    => $response ?? 'No Response Received',
    ];
    $opid = '';
    if (!empty($response)) {
        if ($response['statuscode'] === 'TXN') {
            $opid = $response['data']['txnReferenceId'] ?? '';
            $result['status'] = 'SUCCESS';
        }elseif($response['statuscode'] === 'ERR' || $response['statuscode'] === 'VAL') {
            $result['status'] = 'FAILED';
        } elseif ($response['statuscode'] === 'TUP') {
            $result['status'] = 'PENDING';
        }
        $result['operator_id'] = $opid;
        $result['response']    = $response;
    }

    return $result;
	}


    public static function wb($data, $service)
    {
        $url = 'http://wellbornapi.in/webservices/httpapi/recharge-request-new';
    
        $response = Http::get($url, [
            'api_key'    => env('WB_TOKEN'),
            'acc_no'     => '123886',
            'rech_num'   => $data->mobile_number,
            'amount'     => $data->amount,
            'client_key' => $data->txn_id,
            'opr_code'   => $service->wellborn
        ]);
    
        $res = explode(",", $response->body());
    
        $result = [
            'status'      => 'PENDING',
            'operator_id' => '',
            'response'    => $response->body() ?? 'No Response Received',
        ];
    
        if (!empty($res)) {
            $opid = $res[6] ?? '';
    
            if ($res[0] === 'success') {
                $result['status'] = 'SUCCESS';
            } elseif ($res[0] === 'failure') {
                $result['status'] = 'FAILED';
            } elseif ($res[0] === 'received') {
                $result['status'] = 'SUCCESS';
            }
    
            if ($opid === '' && isset($res[7])) {
                $opid = $res[7];
            }
    
            $result['operator_id'] = $opid;
            $result['response']    = $res;
        }
    
        return $result;
    }
    
    public static function shakti($data, $service)
    {
        $curl = curl_init();
    
        $url = 'https://shaktifintech.in/Api/Service/Recharge2'
             . '?ApiToken='.env('SHK_TOKEN')
             . '&MobileNo=' . $data->mobile_number
             . '&Amount=' . $data->amount
             . '&OpId=' . $service->shakti
             . '&RefTxnId=' . $data->txn_id;
    
        curl_setopt_array($curl, [
            CURLOPT_URL            => $url,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_ENCODING       => '',
            CURLOPT_MAXREDIRS      => 10,
            CURLOPT_TIMEOUT        => 0,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_HTTP_VERSION   => CURL_HTTP_VERSION_1_1,
            CURLOPT_CUSTOMREQUEST  => 'GET',
        ]);
    
        $res = curl_exec($curl);
    
        if (curl_errno($curl)) {
            $error_msg = curl_error($curl);
            curl_close($curl);
            return ['status' => 'FAILED', 'operator_id' => '', 'response' => $error_msg];
        }
    
        curl_close($curl);
    
        $res = json_decode($res, true);
    
        $result = [
            'status'      => 'PENDING',
            'operator_id' => 'NA',
            'response'    => $res ?? 'No Response Received',
        ];
    
        if (!empty($res) && isset($res['STATUS'])) {
            $opid = $res['OPTXNID'] ?? '';
    
            if ($res['STATUS'] == 1) {
                $result['status'] = 'SUCCESS';
            } elseif ($res['STATUS'] == 3) {
                $result['status'] = 'FAILED';
            } else {
                $result['status'] = 'PENDING';
            }
    
            $result['operator_id'] = $opid;
        }
    
        return $result;
    }
    public static function mrb($data, $service)
    {
        $uri = 'https://mrobotics.in/api/recharge';
        $api_key = env('MR1_TOKEN');
    
        $curl = curl_init();
        curl_setopt_array($curl, [
            CURLOPT_URL            => $uri,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_ENCODING       => "",
            CURLOPT_MAXREDIRS      => 10,
            CURLOPT_TIMEOUT        => 30,
            CURLOPT_HTTP_VERSION   => CURL_HTTP_VERSION_1_1,
            CURLOPT_CUSTOMREQUEST  => "POST",
            CURLOPT_POSTFIELDS     => http_build_query([
                "api_token"  => $api_key,
                "mobile_no"  => $data->mobile_number,
                "amount"     => $data->amount,
                "company_id" => $service->mrobotics,
                "order_id"   => $data->txn_id,
                "is_stv"     => "false"
            ]),
        ]);
    
        $res = curl_exec($curl);
        curl_close($curl);
    
        $res = json_decode($res, true);
    
        $result = [
            'status'      => 'PENDING',
            'operator_id' => 'NA',
            'response'    => $res ? json_encode($res) : 'No Response Received', 
            'roffer'      => $res['roffer'] ?? 0,
        ];
    
        if (!empty($res) && isset($res['status'])) {
            $opid = $res['tnx_id'] ?? '';
    
            if ($res['status'] === 'success') {
                $result['status'] = 'SUCCESS';
            } elseif ($res['status'] === 'failure') {
                $result['status'] = 'FAILED';
            } else {
                $result['status'] = 'PENDING';
            }
    
            $result['operator_id'] = $opid ?: 'NA';
        }
    
        return $result;
    }
    
    
    public static function aone($data, $service)
    {
            $url = 'https://a1rechargesolutions.in/API/TransactionAPI';
            $response = Http::get($url, [
                'UserID'       => '11698',
                'Token'        => env('AONE_TOKEN'),
                'Account'      => $data->mobile_number,
                'Amount'       => $data->amount,
                'APIRequestID' => $data->txn_id,
                'SPKey'        => $service->a_one,
                'Format'       => 1
            ]);
            $spc = $response->json();
            $result = [
                'status'       => 'PENDING',
                'operator_id'  => 'NA',
                'response'     => $spc??'TRANSACTION PENDING',
                'balance'      => 0,
            ];
            if (!empty($spc)) {
                $opid = $spc['opid'] ?? 'NA';
                if (isset($spc['status'])) {
                    if ($spc['status'] == 2) {
                        $result['status']   = 'SUCCESS';
                        $result['response'] = 'TRANSACTION SUCCESSFUL';
                    } elseif ($spc['status'] == 3) {
                        $result['status']   = 'FAILED';
                        $result['response'] = $spc['msg'] ?? 'FAILED';
                    } elseif ($spc['status'] == 1) {
                        $result['status']   = 'PENDING';
                        $result['response'] = 'TRANSACTION PENDING';
                    }
                }
                $result['operator_id'] = $opid;
            }
            return $result;
    }
    public static function indicore($data, $service)
    {
  
            $url = 'https://eabi.indicore.net/live';
            $payload = [
                'signature'  => env('INDICORE_SIGNATURE'),
                'rnum'       => $data->mobile_number,
                'ramt'       => $data->amount,
                'optr'       => $service->indicore,         
                'type'       => $service->sub_type=="dth"?'DTH':'REC',              
                'cack'       => $data->txn_id, 
             
            ];
            $indicoreResponse = Indicore::service($url, 'POST', $payload);
            // return $indicoreResponse;
            if (!$indicoreResponse) {
                return [
                    'status'   => 'PENDING',
                    'response' => 'No response from Indicore API',
                ];
            }
            $parts = explode('|', trim($indicoreResponse));
    
            $code = $parts[0] ?? null;
            $msg  = strtoupper(trim($parts[1] ?? ''));
            $custRef = $parts[2] ?? 'NA';
            $operatorRef = $parts[3] ?? '0';
            $indicoreRef = $parts[4] ?? '0';
    
            $result = [
                'status'       => 'FAILED',
                'response'     => 'Unknown Error',
                'operator_id'  => $operatorRef,
                'indicore_ref' => $indicoreRef,
            ];
            switch ($code) {
                case '200':
                    if ($msg === 'SUCCESS') {
                        $result['status'] = 'SUCCESS';
                        $result['response'] = $indicoreResponse;
                    } elseif ($msg === 'ACCEPTED') {
                        $result['status'] = 'PENDING';
                         $result['operator_id'] = 'Under process';
                        
                        $result['response'] = $indicoreResponse;
                    }
                    break;
    
                case '201':
                    $result['status'] = 'PENDING';
                    $result['operator_id'] = 'Transaction pending';
                    $result['response'] = $indicoreResponse;
                    break;
    
                case '202':
                case '203':
                case '204':
                    $result['status'] = 'FAILED';
                    $result['operator_id'] = 'FAILED';
                    $result['response'] = $indicoreResponse;
                    break;
    
                case '403':
                    if (str_contains($msg, 'SUSPENDED')) {
                        $result['status'] = 'FAILED';
                        $result['operator_id'] = 'FAILED';
                        $result['response'] = $indicoreResponse;
                    } elseif (str_contains($msg, 'INSUFFICIENT')) {
                        $result['status'] = 'FAILED';
                        $result['response'] = $indicoreResponse;
                    }
                    break;
    
                case '503':
                    $result['status'] = 'FAILED';
                    $result['operator_id'] = 'FAILED';
                    $result['response'] = $indicoreResponse;
                    break;
    
                default:
                    $result['status'] = 'PENDING';
                    $result['operator_id'] = 'PENDING';
                    $result['response'] = $indicoreResponse;
            }
    
            return $result;
    }




}
