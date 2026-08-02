<?php

namespace App\Modules\WalletTransfer\Models;

use Illuminate\Database\Eloquent\Model;
use App\Modules\User\Models\User;
class WalletTransaction extends Model
{
    protected $table = 'wallet_transactions';

    protected $fillable = [
        'user_id',
        'reciver_id',
        'amount',
        'order_id',
        'client_ref',
        'status'
    ];
    
    public function user()
    {
        return $this->belongsTo(User::class, 'user_id');
    }
    public function receiver()
{
    return $this->belongsTo(User::class, 'reciever_id');
}
}
