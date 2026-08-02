<?php

namespace App\Helper;

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
