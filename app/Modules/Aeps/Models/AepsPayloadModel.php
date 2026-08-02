<?php

namespace App\Modules\Aeps\Models;

use Carbon\Carbon;
use Auth;
class AepsPayloadModel
{
    /**
     * Encrypt Aadhaar using AES-256-CBC
     */
    public static function encryptAadhaar(string $aadhaarNumber): string
    {
        $encryptionKey = env('ENCKEY');
        $ivlen = openssl_cipher_iv_length('aes-256-cbc');
        $iv = openssl_random_pseudo_bytes($ivlen);

        $ciphertext = openssl_encrypt(
            $aadhaarNumber,
            'aes-256-cbc',
            $encryptionKey,
            OPENSSL_RAW_DATA,
            $iv
        );

        return base64_encode($iv . $ciphertext);
    }

    /**
     * Build payload for AEPS Outlet Login
     */
   public static function buildPayload( $data, $encryptedAadhaar)
    {
        $payload = [
            "type"      => $data['type'] ?? '',
            "latitude"  => $data['latitude'] ?? '',
            "longitude" => $data['longitude'] ?? '',
             "outlet_id" => $data['outlet_id'] ,
             "AadharNumber" =>$data['AadharNumber'],
            // "biometricData" => [
                "encryptedAadhaar" => $encryptedAadhaar,
                "dc"        => $data['dc'] ?? '',
                "ci"        => $data['ci'] ?? '',
                "pf"        => 'WEB',
                "hmac"      => $data['hmac'] ?? '',
                "dpId"      => $data['dpId'] ?? '',
                "mc"        => $data['mc'] ?? '',
                "pidDataType" => $data['pidDataType'] ?? '',
                "sessionKey"=> $data['sessionKey'] ?? '',
                "mi"        => $data['mi'] ?? '',
                "rdsId"     => $data['rdsId'] ?? '',
                "errCode"   => "0",
                "errInfo"   => "Success",
                "fCount"    => $data['fCount'] ?? '',
                "fType"     => "2",
                "iCount"    => 0,
                "iType"     => "",
                "pCount"    => 0,
                "pType"     => "",
                "srno"      => $data['srno'] ?? '',
                "sysid"     => "",
                "ts"        => Carbon::now()->toDateTimeString(),
                "pidData"   => $data['pidData'] ?? '',
                "qScore"    => $data['qScore'] ?? '',
                "nmPoints"  => $data['nmPoints'] ?? '',
                "rdsVer"    => $data['rdsVer'] ?? ''
            // ]
        ];
    
        return json_encode($payload, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
    }
    public static function balanceEnquiryPayload($data, $encryptedAadhaar)
    {
        // 1. Secret key
        $secretKey = env('GURU_HKEY');  
    
        // 2. Build string for hashing (as per API doc order)
        $hashString = $data['number']
            . ($data['AadharNumber'] ?? '')   // corrected key spelling
            . ($data['BankName'] ?? '')
            . "19"  // operatorCode
            . ($data['txn_id'] ?? '')
            . $secretKey;
    
        // 3. Generate hash
        $hashData = hash('sha256', $hashString);
    
        // 4. Build payload
        $payload = [
            "bank_iin"        => $data['bank_iin'] ?? '',
            "latitude"        => $data['latitude'] ?? '', 
            "BankName"        => $data['BankName'] ?? '',
            "longitude"       => $data['longitude'] ?? '',
            "outlet_id"       => $data['outlet_id'] ?? '',
            "pf"              => $data['pf'] ?? '',
            "client_id"       => $data['txn_id'] ?? '',
            "mobileNumber"    => $data['number'] ?? '',
            "operatorCode"    => 19,
            "user_agent"      => $data['user_agent'] ?? request()->header('User-Agent'),
            "ip"              => $data['ip'] ?? request()->ip(),
            "externalRef"     => $data['order_id'] ?? uniqid("ORD"),
            "encryptedAadhaar"=> $encryptedAadhaar,
            "AadharNumber"   => $data['AadharNumber'] ?? '',  // corrected spelling
            "dc"              => $data['dc'] ?? '',
            "ci"              => $data['ci'] ?? '',
            "generatedHash"   => $hashData,
            "hmac"            => $data['hmac'] ?? '',
            "dpId"            => $data['dpId'] ?? '',
            "mc"              => $data['mc'] ?? '',
            "pidDataType"     => $data['pidDataType'] ?? '',
            "sessionKey"      => $data['sessionKey'] ?? '',
            "mi"              => $data['mi'] ?? '',
            "rdsId"           => $data['rdsId'] ?? '',
            "errCode"         => "0",
            "errInfo"         => "Success",
            "fCount"          => $data['fCount'] ?? 0,
            "fType"           => "2",
            "iCount"          => 0,
            "iType"           => "",
            "pCount"          => 0,
            "pType"           => "",
            "srno"            => $data['srno'] ?? '',
            "sysid"           => "",
            "ts"              => Carbon::now()->toDateTimeString(),
            "pidData"         => $data['pidData'] ?? '',
            "qScore"          => $data['qScore'] ?? '',
            "nmPoints"        => $data['nmPoints'] ?? '',
            "rdsVer"          => $data['rdsVer'] ?? ''
        ];
    
        return json_encode($payload, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
    }
     public static function miniStatementPayload($data, $encryptedAadhaar)
    {
        // 1. Secret key
        $secretKey = env('GURU_HKEY');  
    
        // 2. Build string for hashing (as per API doc order)
        $hashString = $data['number']
            . ($data['AadharNumber'] ?? '')   // corrected key spelling
            . ($data['BankName'] ?? '')
            . "19"  // operatorCode
            . ($data['txn_id'] ?? '')
            . $secretKey;
    
        // 3. Generate hash
        $hashData = hash('sha256', $hashString);
    
        // 4. Build payload
        $payload = [
            "bank_iin"        => $data['bank_iin'] ?? '',
            "latitude"        => $data['latitude'] ?? '', 
            "BankName"        => $data['BankName'] ?? '',
            "longitude"       => $data['longitude'] ?? '',
            "outlet_id"       => $data['outlet_id'] ?? '',
            "pf"              => $data['pf'] ?? '',
            "client_id"       => $data['txn_id'] ?? '',
            "mobileNumber"    => $data['number'] ?? '',
            "operatorCode"    => 19,
            "user_agent"      => $data['user_agent'],
            "ip"              => $data['ip'],
            "externalRef"     => $data['order_id'] ?? uniqid("ORD"),
            "encryptedAadhaar"=> $encryptedAadhaar,
            "AadharNumber"   => $data['AadharNumber'] ?? '',  // corrected spelling
            "dc"              => $data['dc'] ?? '',
            "ci"              => $data['ci'] ?? '',
            "generatedHash"   => $hashData,
            "hmac"            => $data['hmac'] ?? '',
            "dpId"            => $data['dpId'] ?? '',
            "mc"              => $data['mc'] ?? '',
            "pidDataType"     => $data['pidDataType'] ?? '',
            "sessionKey"      => $data['sessionKey'] ?? '',
            "mi"              => $data['mi'] ?? '',
            "rdsId"           => $data['rdsId'] ?? '',
            "errCode"         => "0",
            "errInfo"         => "Success",
            "fCount"          => $data['fCount'] ?? 0,
            "fType"           => "2",
            "iCount"          => 0,
            "iType"           => "",
            "pCount"          => 0,
            "pType"           => "",
            "srno"            => $data['srno'] ?? '',
            "sysid"           => "",
            "ts"              => Carbon::now()->toDateTimeString(),
            "pidData"         => $data['pidData'] ?? '',
            "qScore"          => $data['qScore'] ?? '',
            "nmPoints"        => $data['nmPoints'] ?? '',
            "rdsVer"          => $data['rdsVer'] ?? ''
        ];
    
        return json_encode($payload, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
    }
        public static function threeWayReconFp($data){
        $response = json_decode($data['api_response'], true);
        $txnDate = date('d-m-Y',strtotime($data['created_at']));
        //$payload['url'] = 'https://fpuat.tapits.in/fpcollectservice_uat/api/threeway/aggregators';
        $payload['url'] = 'https://fpanalytics.tapits.in/fpcollectservice/api/threeway/aggregators';
        $payload['method'] = 'POST';
        $payload['txnDate'] = $txnDate;
        $payload['post'] = '{"merchantTransactionId":"'.$data['txn_id'].'","fingpayTransactionId":"'.$response['data']['fpTransactionId'].'","transactionRrn":"'.$response['data']['bankRRN'].'","responseCode":"'.$response['data']['responseCode'].'","transactionDate":"'.$txnDate.'","serviceType":"'.$response['data']['transactionType'].'"}';
        return $payload;
    }

    public static function cashWithdrawPayload(array $data, string $encryptedAadhaar): string
    {
                $secretKey = env('GURU_HKEY');  
    
        // 2. Build string for hashing (as per API doc order)
        $hashString = $data['number'].$data['amount'].$data['AadhaarNumber'].$data['BankName']."19".$data['txn_id']
            . $secretKey;
       $hashData = hash('sha256', $hashString);
        $payload = [
            "bank_iin"     => $data['bank_iin'] ?? '',
            "latitude"    => $data['latitude'] ?? '',
            "longitude"   => $data['longitude'] ?? '',
            "mobileNumber"      => $data['number'] ?? '',
             "BankName"        => $data['BankName'] ?? '',
            "transferAmount"      => $data['amount'] ?? '',
            "user_agent"      => $data['user_agent'],
            "ip"              => $data['ip'],
             "AadharNumber" => $data['AadhaarNumber'],
             "operatorCode"    => 19,
             "outlet_id"       => $data['outlet_id'] ?? '',
              "generatedHash"   => $hashData,
             "pf"              => $data['pf'] ?? '',
            "client_id"       => $data['txn_id'] ?? '',
            // "biometricData" => [
                "encryptedAadhaar" => $encryptedAadhaar,
                "dc"        => $data['dc'] ?? '',
                "ci"        => $data['ci'] ?? '',
                "hmac"      => $data['hmac'] ?? '',
                "dpId"      => $data['dpId'] ?? '',
                "mc"        => $data['mc'] ?? '',
                "pidDataType" => $data['pidDataType'] ?? '',
                "sessionKey"  => $data['sessionKey'] ?? '',
                "mi"        => $data['mi'] ?? '',
                "rdsId"     => $data['rdsId'] ?? '',
                "errCode"   => "0",
                "errInfo"   => "Success",
                "fCount"    => $data['fCount'] ?? 0,
                "fType"     => "2",
                "iCount"    => 0,
                "iType"     => "",
                "pCount"    => 0,
                "pType"     => "",
                "srno"      => $data['srno'] ?? '',
                "sysid"     => "",
                "ts"        => Carbon::now()->toDateTimeString(),
                "pidData"   => $data['pidData'] ?? '',
                "qScore"    => $data['qScore'] ?? '',
                "nmPoints"  => $data['nmPoints'] ?? '',
                "rdsVer"    => $data['rdsVer'] ?? '',
            // ]
        ];
    
        return json_encode($payload, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
    }
    // public static function merchantOnboardingFp(array $data)
    // {
    //     $user = Auth::user();
    //     $merchantCode = 'MOB' . $user->id;
    //     $pwd = md5('1234d');
    
    //     $payload = [
    //         "username"        => "saimoneyd",
    //         "password"        => $pwd,
    //         "ipAddress"       => $data['ip'] ?? "103.49.124.232",
    //         "latitude"        => $data['Latitude'] ?? "74.52",
    //         "longitude"       => $data['Longitude'] ?? "78.45",
    //         "supermerchantId" => "1234",
    //         "merchant"        => [
    //             "merchantLoginId"       => $merchantCode,
    //             "merchantLoginPin"      => md5("1234"),
    //             "firstName"             => $data['FirstName'],
    //             "lastName"              => $data['LastName'],
    //             "middleName"            => "",
    //             "merchantPhoneNumber"   => $data['MobileNumber'],
    //             "merchantAddress"       => [
    //                 "merchantAddress1"      => $data['Address1'],
    //                 "merchantAddress2"      => $data['Address2'],
    //                 "merchantState"         => $data['State'],
    //                 "merchantCityName"      => $data['City'],
    //                 "merchantDistrictName"  => $data['District'],
    //                 "merchantPinCode"       => $data['PinCode'],
    //             ],
    //             "companyLegalName"      => "JITO FINTECH Private Limited",
    //             "userType"              => $data['BusinessType'],
    //             "companyType"           => "4900",
    //             "emailId"               => $user->email,
    //             "certificateOfIncorporationImage" => "false",
    //             "kyc" => [
    //                 "userPan"           => $data['PanNumber'],
    //                 "aadhaarNumber"     => $data['AadhaarNumber'],
    //                 "gstinNumber"       => "07AALCM0181E1ZN",
    //                 "companyOrShopPan"  => "AALCM0181E",
    //                 "merchantPanImage"  => $data['PanImage'],
    //                 "maskedAadharImage" => $data['AadhaarImage'],
    //                 "shopAndPanImage"   => "false",
    //             ],
    //             "settlementV1" => [
    //                 "companyBankAccountNumber" => $data['BankAccount'],
    //                 "bankIfscCode"             => $data['Ifsc'],
    //                 "companyBankName"          => $data['BankName'],
    //                 "bankAccountName"          => "Savings account",
    //             ],
    //             "tradeBusinessProof"        => "True",
    //             "termsConditionCheck"       => "True",
    //             "cancelledChequeImages"     => "False",
    //             "physicalVerification"      => "True",
    //             "videoKycWithLatLongData"   => "False",
    //             "merchantKycAddressData"    => [
    //                 "shopAddress"   => $data['ShopAddress'],
    //                 "shopCity"      => $data['ShopCity'],
    //                 "shopDistrict"  => $data['ShopDistrict'],
    //                 "shopState"     => $data['ShopState'],
    //                 "shopPincode"   => $data['ShopPinCode'],
    //                 "shopLatitude"  => $data['Latitude'],
    //                 "shopLongitude" => $data['Longitude'],
    //                 "backgroundImageOfShop" => $data['ShopImage'],
    //             ]
    //         ]
    //     ];
    
    //     return json_encode($payload);
    // }
    // public static function merchantOnboardingFp(array $data)
    // {
    //     $user = Auth::user();
    //     $merchantCode = 'MOB' . $user->id;
    
    //     $payload = [
    //         "username"        => "FingPay",
    //         "password"        => "ef73781effc5774100f87fe2f437a435",
    //         "ipAddress"       => $data['ip'] ?? "12.121.11.13",
    //         "latitude"        => $data['Latitude'] ?? "74.52",
    //         "longitude"       => $data['Longitude'] ?? "78.45",
    //         "supermerchantId" => 2,
    
    //         "merchant" => [
    //             "merchantLoginId"      => $merchantCode,
    //             "merchantLoginPin"     => md5("1234"),
    //             "firstName"            => $data['FirstName'],
    //             "lastName"             => $data['LastName'],
    //             "middleName"           => "",
    //             "merchantPhoneNumber"  => $data['MobileNumber'],
    
    //             "merchantAddress" => [
    //                 "merchantAddress1"     => $data['Address1'],
    //                 "merchantAddress2"     => $data['Address2'],
    //                 "merchantState"        => 9, 
    //                 "merchantCityName"     => $data['City'],
    //                 "merchantDistrictName" => $data['District'],
    //                 "merchantPinCode"      => $data['PinCode'],
    //             ],
    
    //             "companyLegalName"           => "JITO FINTECH Private Limited",
    //             "userType"                   => $data['BusinessType'],
    //             "companyType"                => 2,
    //             "emailId"                    => $user->email,
    //             "certificateOfIncorporationImage" => "yes",
    
    //             "kyc" => [
    //                 "userPan"           => $data['PanNumber'],
    //                 "aadhaarNumber"     => $data['AadhaarNumber'],
    //                 // "gstinNumber"       => $data['Gstin'],
    //                 "companyOrShopPan"  => $data['PanNumber'],
    //                 "merchantPanImage"  => $data['PanImage'],
    //                 "maskedAadharImage" => $data['AadhaarImage'],
    //                 "shopAndPanImage"   => "yes",
    //             ],
    
    //             "settlementV1" => [
    //                 "companyBankAccountNumber" => $data['BankAccount'],
    //                 "bankIfscCode"             => $data['Ifsc'],
    //                 "companyBankName"          => $data['BankName'],
    //                 "bankAccountName"          => "Savings account",
    //             ],
    
    //             "tradeBusinessProof"        => "True",
    //             "termsConditionCheck"       => "True",
    //             "cancelledChequeImages"     => "True",
    //             "physicalVerification"      => "True",
    //             "vedioKycWithLatLongData"   => "True", // spelling must match API!
    
    //             "merchantKycAddressData" => [
    //                 "shopAddress"   => $data['ShopAddress'],
    //                 "shopCity"      => $data['ShopCity'],
    //                 "shopDistrict"  => $data['ShopDistrict'],
    //                 "shopState"     => 9,
    //                 "shopPincode"   => $data['ShopPinCode'],
    //                 "shopLatitude"  => $data['Latitude'],
    //                 "shopLongitude" => $data['Longitude'],
    //                 "backgroundImageOfShop" => $data['ShopImage'],
    //             ],
    //         ]
    //     ];
    
    //     return json_encode($payload);
    // }
    // public static function onboardingEkycFp(array $data)
    // {
    //     $user = Auth::user();
    //     $merchantCode = 'MOB' . $user->id;
    //     $pwd = md5('1234d'); 
    
    //     $payload = [
    //         "superMerchantId" => "1234",
    //         "merchantLoginId" => $merchantCode,
    //         "transactionType" => "EKY",
    //         "mobileNumber"    => $data['mobileNumber'],
    //         "aadharNumber"    => $data['aadharNumber'],
    //         "panNumber"       => $data['panNumber'],
    //         "matmSerialNumber"=> "",
    //         "latitude"        => $data['latitude'],
    //         "longitude"       => $data['longitude'],
    //     ];
    
    //     return json_encode($payload); 
    // }
    //  public static function validateOtpFp(array $data)
    // {
    //     $user = Auth::user();
    //     $merchantCode = 'MOB' . $user->id;
    //     $pwd = md5('1234d'); 
    
    //     $payload = [
    //         "superMerchantId" => "1234",
    //         "merchantLoginId" => $merchantCode,
    //         "otp" => $data['otp'],
    //         "primaryKeyId" => $data['primaryKeyId'],
    //         "encodeFPTxnId" => $data['encodeFPTxnId'],
    //     ];
    
    //     return json_encode($payload); 
    // }
    // public static function merchantBiometricKyc(array $data)
    // {
    //     $user = auth()->user();
    
    //     $merchantCode = 'MOB' . $user->id;
    
    //     $captureResponse = [
    //         "errCode"     => $data['captureResponse']['errCode'] ?? '0',
    //         "errInfo"     => $data['captureResponse']['errInfo'] ?? '',
    //         "fCount"      => $data['captureResponse']['fCount'] ?? '',
    //         "fType"       => '2',
    //         "iCount"      => '0',
    //         "iType"       => null,
    //         "pCount"      => '0',
    //         "pType"       => '0',
    //         "nmPoints"    => $data['captureResponse']['nmPoints'] ?? '',
    //         "qScore"      => $data['captureResponse']['qScore'] ?? '',
    //         "dpID"        => $data['captureResponse']['dpId'] ?? '',
    //         "rdsID"       => $data['captureResponse']['rdsId'] ?? '',
    //         "rdsVer"      => $data['captureResponse']['rdsVer'] ?? '',
    //         "dc"          => $data['captureResponse']['dC'] ?? '',
    //         "mi"          => $data['captureResponse']['mI'] ?? '',
    //         "mc"          => $data['captureResponse']['mC'] ?? '',
    //         "ci"          => $data['captureResponse']['cI'] ?? '',
    //         "sessionKey"  => $data['captureResponse']['sessionKey'] ?? '',
    //         "hmac"        => $data['captureResponse']['hMac'] ?? '',
    //         "PidDatatype" => $data['captureResponse']['pidDataType'] ?? '',
    //         "Piddata"     => $data['captureResponse']['pidData'] ?? '',
    //     ];
    
    //     $payload = [
    //         'url'      => 'https://fpekyc.tapits.in/fpekyc/api/ekyc/merchant/php/biometric',
    //         'deviceId' => $data['deviceId'],
    //         'method'   => 'POST',
    //         'post'     => [
    //             "cardnumberORUID" => [
    //                 "adhaarNumber"       => $data['adhaarNumber'],
    //                 "indicatorforUID"    => 0
    //             ],
    //             "requestRemarks" => "",
    //             "captureResponse"=> $captureResponse,
    //             "merchantLoginId"=> $merchantCode,
    //             "superMerchantId"=> 1234,
    //             "primaryKeyId"   => $data['primaryKeyId'],
    //             "encodeFPTxnId"  => $data['encodeFPTxnId']
    //         ]
    //     ];
    
    //     return json_encode($payload, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
    // }
    public static function merchantOnboardingFp($data){
        $user = Auth::user();
        $merchantCode = 'TRANS'.$user->id;
        $pwd = md5('1234d');
        $payload['url'] = 'https://fingpayap.tapits.in/fpaepsweb/api/onboarding/merchant/php/creation/v2';
        $payload['method'] = 'POST';
        $payload['post'] = '{
            "username":"saimoneyd",
            "password":"'.$pwd.'",
            "ipAddress":"103.49.124.232",
            "latitude":"74.52",
            "longitude":"78.45",
            "superMerchantId":"1412",
            "merchant":{
                "merchantLoginId":"'.$merchantCode.'",
                "merchantLoginPin":"1234",
                "firstName":"'.$data['FirstName'].'",
                "lastName":"'.$data['LastName'].'",
                "middleName":" ",
                "merchantPhoneNumber":"'.$data['MobileNumber'].'",
                "merchantAddress":{
                    "merchantAddress1":"'.$data['Address1'].'",
                    "merchantAddress2":"'.$data['Address2'].'",
                    "merchantState":"18",
                    "merchantCityName":"'.$data['City'].'",
                    "merchantDistrictName":"'.$data['District'].'",
                    "merchantPinCode":"'.$data['PinCode'].'"
                },
                "companyLegalName":"JITO FINTECH Private Limited",
                "userType":"'.$data['BusinessType'].'",
                "companyType":"4900",
                "emailId":"'.$user->email.'",
                "certificateOfIncorporationImage":"false",
                "kyc":{
                    "userPan":"'.$data['PanNumber'].'",
                    "aadhaarNumber":"'.$data['AadhaarNumber'].'",
                    "gstinNumber":"24AAVFJ2796H1ZR",
                    "companyOrShopPan":"AALCM0181E",
                    "merchantPanImage":"'.$data['PanImage'].'",
                    "maskedAadharImage":"'.$data['AadhaarImage'].'",
                    "shopAndPanImage":"false"
                },
                "settlementV1":{
                        "companyBankAccountNumber":"'.$data['BankAccount'].'",
                        "bankIfscCode":"'.$data['Ifsc'].'",
                        "companyBankName":"'.$data['BankName'].'",
                        "bankAccountName":"Savings account"
                    },
                "tradeBusinessProof":"True",
                "termsConditionCheck":"True",
                "cancelledChequeImages":"False",
                "physicalVerification":"True",
                "videoKycWithLatLongData":"False",
                "merchantKycAddressData":{
                    "shopAddress":"'.$data['ShopAddress'].'",
                    "shopCity":"'.$data['ShopCity'].'",
                    "shopDistrict":"'.$data['ShopDistrict'].'",
                    "shopState":"18",
                    "shopPincode":"'.$data['ShopPinCode'].'",
                    "shopLatitude":"'.$data['Latitude'].'",
                    "shopLongitude":"'.$data['Longitude'].'",
                    "backgroundImageOfShop":"'.$data['ShopImage'].'"
                }
            }
        }';
        return $payload;
    }
    public static function merchantEkycFp($data){
        $user = Auth::user();
        $merchantCode = 'TRANS'.$user->id;
        $matmSno = (isset($data['matmSerialNumber']))? $data['matmSerialNumber'] : '';
        $payload['url'] = 'https://fpekyc.tapits.in/fpekyc/api/ekyc/merchant/php/sendotp';
        $payload['method'] = 'POST';
        $payload['post'] = '{
            "superMerchantId":"1412",
            "merchantLoginId":"'.$merchantCode.'",
            "transactionType":"EKY",
            "mobileNumber":"'.$data['mobileNumber'].'",
            "aadharNumber":"'.$data['aadharNumber'].'",
            "panNumber":"'.$data['panNumber'].'",
            "matmSerialNumber":"",
            "latitude":"'.$data['latitude'].'",
            "longitude":"'.$data['longitude'].'"
        }';
        return $payload;
    }
    public static function merchantEkycFpApi($data){
        // $user = Auth::user();
        // $merchantCode = 'MOB'.$user->id;
        $matmSno = (isset($data['matmSerialNumber']))? $data['matmSerialNumber'] : '';
        $payload['url'] = 'https://fpekyc.tapits.in/fpekyc/api/ekyc/merchant/php/sendotp';
        $payload['method'] = 'POST';
        $payload['post'] = '{
            "superMerchantId":"1412",
            "merchantLoginId":"'.$data['merchantCode'].'",
            "transactionType":"EKY",
            "mobileNumber":"'.$data['mobileNumber'].'",
            "aadharNumber":"'.$data['aadharNumber'].'",
            "panNumber":"'.$data['panNumber'].'",
            "matmSerialNumber":"",
            "latitude":"'.$data['latitude'].'",
            "longitude":"'.$data['longitude'].'"
        }';
        return $payload;
    }
    public static function validateOtpFpApi($data){
        $user = Auth::user();
        $merchantCode = 'TRANS'.$user->id;
        $payload['url'] = 'https://fpekyc.tapits.in/fpekyc/api/ekyc/merchant/php/validateotp';
        $payload['method'] = 'POST';
        $payload['post'] = '{
            "superMerchantId":"1412",
            "merchantLoginId":"'.$merchantCode.'",
            "otp":"'.$data['otp'].'",
            "primaryKeyId":"'.$data['primaryKeyId'].'",
            "encodeFPTxnId":"'.$data['encodeFPTxnId'].'"
        }';
        return $payload;
    }
    public static function validateOtpFp($data){
        $user = Auth::user();
        $merchantCode = 'TRANS'.$user->id;
        $payload['url'] = 'https://fpekyc.tapits.in/fpekyc/api/ekyc/merchant/php/validateotp';
        $payload['method'] = 'POST';
        $payload['post'] = '{
            "superMerchantId":"1412",
            "merchantLoginId":"'.$merchantCode.'",
            "otp":"'.$data['otp'].'",
            "primaryKeyId":"'.$data['primaryKeyId'].'",
            "encodeFPTxnId":"'.$data['encodeFPTxnId'].'"
        }';
        return $payload;
    }
    public static function merchantBiomatricKyc($data){
        $user = Auth::user();
        //$captureResponse = $data['captureResponse'];
        $captureResponse = '{
            "errCode":"0",
            "errInfo":"'.$data['captureResponse']['errInfo'].'",
            "fCount":"'.$data['captureResponse']['fCount'].'",
            "fType":"2",
            "iCount":"0",
            "iType":null,
            "pCount":"0",
            "pType":"0",
            "nmPoints":"'.$data['captureResponse']['nmPoints'].'",
            "qScore":"'.$data['captureResponse']['qScore'].'",
            "dpID":"'.$data['captureResponse']['dpId'].'",
            "rdsID":"'.$data['captureResponse']['rdsId'].'",
            "rdsVer":"'.$data['captureResponse']['rdsVer'].'",
            "dc":"'.$data['captureResponse']['dC'].'",
            "mi":"'.$data['captureResponse']['mI'].'",
            "mc":"'.$data['captureResponse']['mC'].'",
            "ci":"'.$data['captureResponse']['cI'].'",
            "sessionKey":"'.$data['captureResponse']['sessionKey'].'",
            "hmac":"'.$data['captureResponse']['hMac'].'",
            "PidDatatype":"'.$data['captureResponse']['pidDataType'].'",
            "Piddata":"'.$data['captureResponse']['pidData'].'"
        }';
        $merchantCode = 'TRANS'.$user->id;
        $payload['url'] = 'https://fpekyc.tapits.in/fpekyc/api/ekyc/merchant/php/biometric';
        $payload['deviceId'] = $data['deviceId'];
        $payload['method'] = 'POST';
        $payload['post'] = '{
            "cardnumberORUID":{
                "adhaarNumber":"'.$data['adhaarNumber'].'",
                "indicatorforUID":0
            },
            "requestRemarks":"",
            "captureResponse":'.$captureResponse.',
            "merchantLoginId":"'.$merchantCode.'",
            "superMerchantId":1412,
            "primaryKeyId":'.$data['primaryKeyId'].',
            "encodeFPTxnId":"'.$data['encodeFPTxnId'].'"
        }';
        return $payload;
    }
//   public static function twoFaFp($data)
// {
//     $user = Auth::user();
//     $reqRemark = Carbon::now()->timestamp;

//     // Create captureResponse array
//     $captureResponse = [
//         "errCode"      => "0",
//         "errInfo"      => $data['captureResponse']['errInfo'],
//         "fCount"       => $data['captureResponse']['fCount'],
//         "fType"        => "2",
//         "iCount"       => "0",
//         "iType"        => "",
//         "pCount"       => "0",
//         "pType"        => "",
//         "nmPoints"     => $data['captureResponse']['nmPoints'],
//         "qScore"       => $data['captureResponse']['qScore'],
//         "dpID"         => $data['captureResponse']['dpId'],
//         "rdsID"        => $data['captureResponse']['rdsId'],
//         "rdsVer"       => $data['captureResponse']['rdsVer'],
//         "dc"           => $data['captureResponse']['dC'],
//         "mi"           => $data['captureResponse']['mI'],
//         "mc"           => $data['captureResponse']['mC'],
//         "ci"           => $data['captureResponse']['cI'],
//         "sessionKey"   => $data['captureResponse']['sessionKey'],
//         "hmac"         => $data['captureResponse']['hMac'],
//         "PidDatatype"  => $data['captureResponse']['pidDataType'],
//         "Piddata"      => $data['captureResponse']['pidData']
//     ];

//     $merchantCode = 'TRANS' . $user->id;

//     $payload['url'] = 'https://fingpayap.tapits.in/fpaepsservice/auth/tfauth/merchant/php/validate/aadhar';
//     $payload['deviceId'] = $data['deviceId'];
//     $payload['method'] = 'POST';

//     $payload['post'] = json_encode([
//         "cardnumberORUID" => [
//             "adhaarNumber" => $data['adhaarNumber'],
//             "indicatorforUID" => 0
//         ],
//         "requestRemarks" => "",
//         "latitude" => $data['latitude'],
//         "longitude" => $data['longitude'],
//         "transactionType" => "AUO",
//         "merchantPin" => "81dc9bdb52d04dc20036dbd8313ed055",
//         "captureResponse" => $captureResponse,
//         "merchantUserName" => $merchantCode,
//         "superMerchantId" => 1412,
//         "merchantTranId" => $reqRemark,
//         "serviceType" => "AEPS",
//         "mobileNumber" => $user->username
//     ]);

//     return $payload;
// }
// public static function twoFaFp($data)
// {
//     $user = Auth::user();
//     $merchantCode = 'TRANS' . $user->id;
//     $transactionId = Carbon::now()->timestamp;

//     // Build captureResponse array
//     $captureResponse = [
//         'errCode' => '0',
//         'errInfo' => $data['captureResponse']['errInfo'],
//         'fCount' => $data['captureResponse']['fCount'],
//         'fType' => '2',
//         'iCount' => '0',
//         'iType' => '',
//         'pCount' => '0',
//         'pType' => '',
//         'nmPoints' => $data['captureResponse']['nmPoints'],
//         'qScore' => $data['captureResponse']['qScore'],
//         'dpID' => $data['captureResponse']['dpId'],
//         'rdsID' => $data['captureResponse']['rdsId'],
//         'rdsVer' => $data['captureResponse']['rdsVer'],
//         'dc' => $data['captureResponse']['dC'],
//         'mi' => $data['captureResponse']['mI'],
//         'mc' => $data['captureResponse']['mC'],
//         'ci' => $data['captureResponse']['cI'],
//         'sessionKey' => $data['captureResponse']['sessionKey'],
//         'hmac' => $data['captureResponse']['hMac'],
//         'PidDatatype' => $data['captureResponse']['pidDataType'],
//         'Piddata' => $data['captureResponse']['pidData'],
//     ];
//     // $captureResponse = '{
//     //         "errCode":"0",
//     //         "errInfo":"'.$data['captureResponse']['errInfo'].'",
//     //         "fCount":"'.$data['captureResponse']['fCount'].'",
//     //         "fType":"2",
//     //         "iCount":"0",
//     //         "iType":"",
//     //         "pCount":"0",
//     //         "pType":"",
//     //         "nmPoints":"'.$data['captureResponse']['nmPoints'].'",
//     //         "qScore":"'.$data['captureResponse']['qScore'].'",
//     //         "dpID":"'.$data['captureResponse']['dpId'].'",
//     //         "rdsID":"'.$data['captureResponse']['rdsId'].'",
//     //         "rdsVer":"'.$data['captureResponse']['rdsVer'].'",
//     //         "dc":"'.$data['captureResponse']['dC'].'",
//     //         "mi":"'.$data['captureResponse']['mI'].'",
//     //         "mc":"'.$data['captureResponse']['mC'].'",
//     //         "ci":"'.$data['captureResponse']['cI'].'",
//     //         "sessionKey":"'.$data['captureResponse']['sessionKey'].'",
//     //         "hmac":"'.$data['captureResponse']['hMac'].'",
//     //         "PidDatatype":"'.$data['captureResponse']['pidDataType'].'",
//     //         "Piddata":"'.$data['captureResponse']['pidData'].'"
//     //     }';

//     // Build post data
//     $postData = [
//         'cardnumberORUID' => [
//             'adhaarNumber' => $data['adhaarNumber'],
//             'indicatorforUID' => 0
//         ],
//         'requestRemarks' => '',
//         'latitude' => (float) $data['latitude'],
//         'longitude' => (float) $data['longitude'],
//         'transactionType' => 'AUO',
//         'merchantPin' => '81dc9bdb52d04dc20036dbd8313ed055',
//         'captureResponse' => $captureResponse,
//         'merchantUserName' => $merchantCode,
//         'superMerchantId' => 1412,
//         'merchantTranId' => $transactionId,
//         'serviceType' => 'AEPS',
//         'mobileNumber' => $user->mobile,
//     ];

//     // Encode payload to JSON
//     $payload = [
//         'url' => 'https://fingpayap.tapits.in/fpaepsservice/auth/tfauth/merchant/php/validate/aadhar',
//         'deviceId' => $data['deviceId'],
//         'method' => 'POST',
//         'post' => json_encode($postData, JSON_UNESCAPED_SLASHES)
//     ];

//     return $payload;
// }

    public static function twoFaFp($data){
            $user = Auth::user();
            $reqRemark = Carbon::now()->timestamp;
            //$captureResponse = $data['captureResponse'];
            $captureResponse = '{
                "errCode":"0",
                "errInfo":"'.$data['captureResponse']['errInfo'].'",
                "fCount":"'.$data['captureResponse']['fCount'].'",
                "fType":"2",
                "iType":"0",
                "iCount":"0",
                "pCount":"0",
                "pType":"0",
                "nmPoints":"'.$data['captureResponse']['nmPoints'].'",
                "qScore":"'.$data['captureResponse']['qScore'].'",
                "dpID":"'.$data['captureResponse']['dpId'].'",
                "rdsID":"'.$data['captureResponse']['rdsId'].'",
                "rdsVer":"'.$data['captureResponse']['rdsVer'].'",
                "dc":"'.$data['captureResponse']['dC'].'",
                "mi":"'.$data['captureResponse']['mI'].'",
                "mc":"'.$data['captureResponse']['mC'].'",
                "ci":"'.$data['captureResponse']['cI'].'",
                "sessionKey":"'.$data['captureResponse']['sessionKey'].'",
                "hmac":"'.$data['captureResponse']['hMac'].'",
                "PidDatatype":"'.$data['captureResponse']['pidDataType'].'",
                "Piddata":"'.$data['captureResponse']['pidData'].'"
            }';
            $merchantCode = 'TRANS'.$user->id;
            $payload['url'] = 'https://fingpayap.tapits.in/fpaepsservice/auth/tfauth/merchant/php/validate/aadhar';
            $payload['deviceId'] = $data['deviceId'];
            $payload['method'] = 'POST';
            $payload['post'] = '{
                "cardnumberORUID":{
                    "adhaarNumber":"'.$data['adhaarNumber'].'",
                    "indicatorforUID":0,
                    "nationalBankIdentificationNumber":607152
                },
                "requestRemarks":"hgfjhcj",
                "latitude":'.$data['latitude'].',
                "longitude":'.$data['longitude'].',
                "transactionType":"AUO",
                "merchantPin":"81dc9bdb52d04dc20036dbd8313ed055",
                "captureResponse":'.$captureResponse.',
                "merchantUserName":"'.$merchantCode.'",
                "superMerchantId":1412,
                "merchantTranId":"'.$reqRemark.'",
                "serviceType":"AEPS",
                "mobileNumber":"'.$user->mobile.'"
            }';
            return $payload;
        }
    public static function cashWithdrawlFp($data){
        $user = Auth::user();
        $pwd = md5('1234d');
        //date_default_timezone_set('Asia/Kolkata');
        $timestamp = Carbon::now()->format('d/m/Y H:i:s');
        //$captureResponse = $data['captureResponse'];
        $captureResponse = '{
            "errCode":"0",
            "errInfo":"'.$data['captureResponse']['errInfo'].'",
            "fCount":"'.$data['captureResponse']['fCount'].'",
            "fType":"2",
            "iCount":"0",
            "iType":"",
            "pCount":"0",
            "pType":"",
            "nmPoints":"'.$data['captureResponse']['nmPoints'].'",
            "qScore":"'.$data['captureResponse']['qScore'].'",
            "dpID":"'.$data['captureResponse']['dpId'].'",
            "rdsID":"'.$data['captureResponse']['rdsId'].'",
            "rdsVer":"'.$data['captureResponse']['rdsVer'].'",
            "dc":"'.$data['captureResponse']['dC'].'",
            "mi":"'.$data['captureResponse']['mI'].'",
            "mc":"'.$data['captureResponse']['mC'].'",
            "ci":"'.$data['captureResponse']['cI'].'",
            "sessionKey":"'.$data['captureResponse']['sessionKey'].'",
            "hmac":"'.$data['captureResponse']['hMac'].'",
            "PidDatatype":"'.$data['captureResponse']['pidDataType'].'",
            "Piddata":"'.$data['captureResponse']['pidData'].'"
        }';
        $merchantCode = 'TRANS'.$user->id;
        // $merchantCode = 'TRANS6';
        $payload['url'] = 'https://fingpayap.tapits.in/fpaepsservice/api/cashWithdrawal/merchant/php/withdrawal';
        $payload['deviceId'] = $data['deviceId'];
        $payload['method'] = 'POST';
        $payload['post'] = '{
            "cardnumberORUID":{
                "adhaarNumber":"'.$data['AadharNumber'].'",
                "indicatorforUID":0,
                "nationalBankIdentificationNumber":'.$data['bank_iin'].'
            },
            "captureResponse":'.$captureResponse.',
            "merchantTranId":"'.$data['order_id'].'",
            "languageCode":"en",
            "latitude":'.$data['latitude'].',
            "longitude":'.$data['longitude'].',
            "mobileNumber":'.$data['number'].',
            "paymentType":"B",
            "requestRemarks":"",
            "timestamp":"'.$timestamp.'",
            "transactionAmount":'.$data['amount'].',
            "transactionType":"CW",
            "merchantUserName":"'.$merchantCode.'",
            "merchantPin":"81dc9bdb52d04dc20036dbd8313ed055",
            "superMerchantId":1412
        }';
        return $payload;
    }
    public static function adhaarPay($data){
        $user = Auth::user();
        $pwd = md5('1234d');
        //date_default_timezone_set('Asia/Kolkata');
        $timestamp = Carbon::now()->format('d/m/Y H:i:s');
        //$captureResponse = $data['captureResponse'];
        $captureResponse = '{
            "errCode":"0",
            "errInfo":"'.$data['captureResponse']['errInfo'].'",
            "fCount":"'.$data['captureResponse']['fCount'].'",
            "fType":"2",
            "iCount":"0",
            "iType":"",
            "pCount":"0",
            "pType":"",
            "nmPoints":"'.$data['captureResponse']['nmPoints'].'",
            "qScore":"'.$data['captureResponse']['qScore'].'",
            "dpID":"'.$data['captureResponse']['dpId'].'",
            "rdsID":"'.$data['captureResponse']['rdsId'].'",
            "rdsVer":"'.$data['captureResponse']['rdsVer'].'",
            "dc":"'.$data['captureResponse']['dC'].'",
            "mi":"'.$data['captureResponse']['mI'].'",
            "mc":"'.$data['captureResponse']['mC'].'",
            "ci":"'.$data['captureResponse']['cI'].'",
            "sessionKey":"'.$data['captureResponse']['sessionKey'].'",
            "hmac":"'.$data['captureResponse']['hMac'].'",
            "PidDatatype":"'.$data['captureResponse']['pidDataType'].'",
            "Piddata":"'.$data['captureResponse']['pidData'].'"
        }';
        $merchantCode = 'TRANS'.$user->id;
        // $merchantCode = 'TRANS6';
        $payload['url'] = 'https://fingpayap.tapits.in/fpaepsservice/api/aadhaarPay/merchant/php/pay';
        $payload['deviceId'] = $data['deviceId'];
        $payload['method'] = 'POST';
        $payload['post'] = '{
            "cardnumberORUID":{
                "adhaarNumber":"'.$data['AadharNumber'].'",
                "indicatorforUID":0,
                "nationalBankIdentificationNumber":'.$data['bank_iin'].'
            },
            "captureResponse":'.$captureResponse.',
            "merchantTranId":"'.$data['order_id'].'",
            "languageCode":"en",
            "latitude":'.$data['latitude'].',
            "longitude":'.$data['longitude'].',
            "mobileNumber":'.$data['number'].',
            "paymentType":"B",
            "requestRemarks":"",
            "timestamp":"'.$timestamp.'",
            "transactionAmount":'.$data['amount'].',
            "transactionType":"M",
            "merchantUserName":"'.$merchantCode.'",
            "merchantPin":"81dc9bdb52d04dc20036dbd8313ed055",
            "superMerchantId":1412
        }';
        return $payload;
    }
    public static function balanceInquiryFp($data){
        $user = Auth::user();
        $pwd = md5('1234d');
        //date_default_timezone_set('Asia/Kolkata');
        $timestamp = Carbon::now()->format('d/m/Y H:i:s');
        //$captureResponse = $data['captureResponse'];
        $captureResponse = '{
            "errCode":"0",
            "errInfo":"'.$data['captureResponse']['errInfo'].'",
            "fCount":"'.$data['captureResponse']['fCount'].'",
            "fType":"2",
            "iCount":"0",
            "iType":"",
            "pCount":"0",
            "pType":"",
            "nmPoints":"'.$data['captureResponse']['nmPoints'].'",
            "qScore":"'.$data['captureResponse']['qScore'].'",
            "dpID":"'.$data['captureResponse']['dpId'].'",
            "rdsID":"'.$data['captureResponse']['rdsId'].'",
            "rdsVer":"'.$data['captureResponse']['rdsVer'].'",
            "dc":"'.$data['captureResponse']['dC'].'",
            "mi":"'.$data['captureResponse']['mI'].'",
            "mc":"'.$data['captureResponse']['mC'].'",
            "ci":"'.$data['captureResponse']['cI'].'",
            "sessionKey":"'.$data['captureResponse']['sessionKey'].'",
            "hmac":"'.$data['captureResponse']['hMac'].'",
            "PidDatatype":"X",
            "Piddata":"'.$data['captureResponse']['pidData'].'"
        }';
        $merchantCode = 'TRANS'.$user->id;
        $payload['url'] = 'https://fingpayap.tapits.in/fpaepsservice/api/balanceInquiry/merchant/php/getBalance';
        $payload['deviceId'] = $data['deviceId'];
        $payload['method'] = 'POST';
        $payload['post'] = '{
            "cardnumberORUID":{
                "adhaarNumber":"'.$data['AadharNumber'].'",
                "indicatorforUID":0,
                "nationalBankIdentificationNumber":'.$data['bank_iin'].'
            },
            "captureResponse":'.$captureResponse.',
            "merchantTransactionId":"'.$data['order_id'].'",
            "languageCode":"en",
            "latitude":'.$data['latitude'].',
            "longitude":'.$data['longitude'].',
            "mobileNumber":'.$data['number'].',
            "paymentType":"B",
            "requestRemarks":"",
            "timestamp":"'.$timestamp.'",
            "transactionType":"BE",
            "merchantUserName":"'.$merchantCode.'",
           "merchantPin": "81dc9bdb52d04dc20036dbd8313ed055",
            "superMerchantId":1412
        }';
        return $payload;
    }
    public static function miniStatementFp($data){
        $user = Auth::user();
        $pwd = md5('1234d');
        //date_default_timezone_set('Asia/Kolkata');
        $timestamp = Carbon::now()->format('d/m/Y H:i:s');
        //$captureResponse = $data['captureResponse'];
        $captureResponse = '{
            "errCode":"0",
            "errInfo":"'.$data['captureResponse']['errInfo'].'",
            "fCount":"'.$data['captureResponse']['fCount'].'",
            "fType":"2",
            "iCount":"0",
            "iType":"",
            "pCount":"0",
            "pType":"",
            "nmPoints":"'.$data['captureResponse']['nmPoints'].'",
            "qScore":"'.$data['captureResponse']['qScore'].'",
            "dpID":"'.$data['captureResponse']['dpId'].'",
            "rdsID":"'.$data['captureResponse']['rdsId'].'",
            "rdsVer":"'.$data['captureResponse']['rdsVer'].'",
            "dc":"'.$data['captureResponse']['dC'].'",
            "mi":"'.$data['captureResponse']['mI'].'",
            "mc":"'.$data['captureResponse']['mC'].'",
            "ci":"'.$data['captureResponse']['cI'].'",
            "sessionKey":"'.$data['captureResponse']['sessionKey'].'",
            "hmac":"'.$data['captureResponse']['hMac'].'",
            "PidDatatype":"'.$data['captureResponse']['pidDataType'].'",
            "Piddata":"'.$data['captureResponse']['pidData'].'"
        }';
        $merchantCode = 'TRANS'.$user->id;
        //  $merchantCode = 'TRANS6';
        $payload['url'] = 'https://fingpayap.tapits.in/fpaepsservice/api/miniStatement/merchant/php/statement';
        $payload['deviceId'] = $data['deviceId'];
        $payload['method'] = 'POST';
        $payload['post'] = '{
            "cardnumberORUID":{
                "adhaarNumber":"'.$data['AadharNumber'].'",
                "indicatorforUID":0,
                "nationalBankIdentificationNumber":'.$data['bank_iin'].'
            },
            "captureResponse":'.$captureResponse.',
            "merchantTranId":"'.$data['order_id'].'",
            "languageCode":"en",
            "latitude":'.$data['latitude'].',
            "longitude":'.$data['longitude'].',
            "mobileNumber":'.$data['number'].',
            "paymentType":"B",
            "requestRemarks":"",
            "timestamp":"'.$timestamp.'",
            "transactionType":"MS",
            "merchantUserName":"'.$merchantCode.'",
            "merchantPin":"81dc9bdb52d04dc20036dbd8313ed055",
            "superMerchantId":1412
        }';
        return $payload;
    }


 

}
