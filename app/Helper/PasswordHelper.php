<?php

namespace App\Helper;

class PasswordHelper {
    
    
    public static function generateRandomPassword($length = 5) {
        $letters = '';
        $characters = 'abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ';
        for ($i = 0; $i < $length; $i++) {
            $letters .= $characters[rand(0, strlen($characters) - 1)];
        }
        $numbers = rand(1000, 9999);
        return $letters . '@' . $numbers;
    }
}
