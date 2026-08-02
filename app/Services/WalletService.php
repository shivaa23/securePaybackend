<?php

namespace App\Services;

use App\Models\User;

class WalletService
{
    public function setBalance(User $user, float $amount): void
    {
        $user->wallet_balance = $amount;
        $user->save();
    }

    public function increment(User $user, float $amount): void
    {
        $balance = $user->wallet_balance; // already decrypted by accessor
        $user->wallet_balance = $balance + $amount;
        $user->save();
    }

    public function decrement(User $user, float $amount): void
    {
        $balance = $user->wallet_balance;
        $user->wallet_balance = max(0, $balance - $amount);
        $user->save();
    }

    public function getBalance(User $user): float
    {
        return $user->wallet_balance; // accessor handles decryption
    }

    /**
     * Get sum of all wallet balances
     * (You must decrypt each row in PHP, can't do directly in SQL)
     */
    public function getTotalBalance(): float
    {
        $total = 0;
        foreach (User::all(['wallet_balance']) as $user) {
            try {
                $total += decrypt($user->getRawOriginal('wallet_balance'));
            } catch (\Exception $e) {
                // skip invalid balances
            }
        }
        return $total;
    }
}