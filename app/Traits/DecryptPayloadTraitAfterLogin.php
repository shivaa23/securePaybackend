<?php

namespace App\Traits;

use Auth;
use Illuminate\Support\Facades\Crypt;

trait DecryptPayloadTraitAfterLogin
{
    public function decryptPayload($encryptedData, $hash)
    {
        $user = Auth::user();
     
        $secretKey = Crypt::decrypt($user->encryption_key);
    
        $calculatedHash = hash_hmac('sha256', $encryptedData, $secretKey);
        if ($calculatedHash !== $hash) {
            return ['error' => 'Hash mismatch - possible tampering'];
        }

        $cipherText = base64_decode($encryptedData);
        $decrypted = openssl_decrypt($cipherText, 'AES-128-ECB', $secretKey, OPENSSL_RAW_DATA);

        if (!$decrypted) {
            return ['error' => 'Decryption failed'];
        }

        return json_decode($decrypted, true);
    }
}
