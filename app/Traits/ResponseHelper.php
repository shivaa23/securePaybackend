<?php

namespace App\Helpers;
/*
class ResponseHelper
{
    public static function success($message = '', $data = [], $status = 200)
    {
        //  $secretKey =  '1234567890123456';
        //   $hashData=hash_hmac('sha256', $data, $secretKey);
        return response()->json([
            'status' => true,
            'message' => $message,
            'data' => $data
        ], $status);
    }

    public static function error($message = 'Something went wrong', $errors = [], $status = 400)
    {
        return response()->json([
            'status' => false,
            'message' => $message,
            'errors' => $errors
        ], $status);
    }
}
*/
// class ResponseHelper
// {
//     private static $secretKey = env('USER_ENCRYPTDATA_KEY');
//     private static $encryptionMethod = 'AES-128-CBC';

//     public static function success($message = '', $data = [], $status = 200)
//     {
//         $encrypted = self::encryptData($data);

//         return response()->json([
//             'status' => true,
//             'message' => $message,
//             'data' => $data,
//             'encrypted_data' => $encrypted['encrypted'],
//             'encryption_key' => $encrypted['key']
//         ], $status);
//     }

//     public static function error($message = 'Something went wrong', $errors = [], $status = 400)
//     {
//         $encrypted = self::encryptData($errors);

//         return response()->json([
//             'status' => false,
//             'message' => $message,
//             'errors' => $errors,
//             'encrypted_errors' => $encrypted['encrypted'],
//             'encryption_key' => $encrypted['key']
//         ], $status);
//     }

//     private static function encryptData($data)
//     {
//         $iv = openssl_random_pseudo_bytes(16);
//         $jsonData = json_encode($data);
//         $encrypted = openssl_encrypt($jsonData, self::$encryptionMethod, self::$secretKey, 0, $iv);

//         return [
//             'encrypted' => $encrypted,
//             'key' => base64_encode($iv),
//         ];
//     }


// }
class ResponseHelper
{
    private static $secretKey;
    private static $encryptionMethod = 'AES-128-CBC';
    private static $iv;
   private static function initialize()
    {
        self::$secretKey = env('USER_ENCRYPTDATA_KEY'); 
        self::$iv = env('USER_ENCRYPTDATA_IV'); 
    }

    // public static function success($message = '', $data = [], $status = 200)
    // {
    //     self::initialize();
    //     $encrypted = self::encryptData($data);

    //     return response()->json([
    //         'status' => true,
    //         'message' => $message,
    //         'data' => $data,
    //         'encrypted_data' => $encrypted
    //     ], $status);
    // }
    public static function success($message = '', $data = [], $status = 200)
{
    self::initialize();
    $encrypted = self::encryptData($data);

    return response()->json([
        'status' => true,
        'message' => $message,
        'data' => $data,
        'encrypted_data' => $encrypted
    ], $status, [], JSON_UNESCAPED_UNICODE);
}


    public static function error($message = 'Something went wrong', $errors = [], $status = 400)
    {
        self::initialize();
        $encrypted = self::encryptData($errors);

        return response()->json([
            'status' => false,
            'message' => $message,
            'errors' => $errors,
            'encrypted_errors' => $encrypted
        ], $status);
    }

    private static function encryptData($data)
    {
        $jsonData = json_encode($data);
        $encrypted = openssl_encrypt(
            $jsonData,
            self::$encryptionMethod,
            self::$secretKey,
            0,
            self::$iv
        );

        return $encrypted;
    }
}
