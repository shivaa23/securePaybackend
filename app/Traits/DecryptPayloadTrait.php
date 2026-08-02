<?php

namespace App\Traits;

trait DecryptPayloadTrait
{
    public function decryptPayload($encryptedData, $hash)
    {
        $secretKey = env('USER_ENCRYPTDATA_KEY');
        $iv = env('USER_ENCRYPTDATA_IV'); 

        $calculatedHash = hash_hmac('sha256', $encryptedData, $secretKey);
        if ($calculatedHash !== $hash) {
            return ['error' => 'Hash mismatch - possible tampering'];
        }

        $cipherText = base64_decode($encryptedData);
        $decrypted = openssl_decrypt($cipherText, 'AES-128-CBC', $secretKey, OPENSSL_RAW_DATA, $iv);

        if (!$decrypted) {
            return ['error' => 'Decryption failed'];
        }

        return json_decode($decrypted, true);
    }
}
