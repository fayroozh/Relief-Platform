<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class WalletTransaction extends Model
{
    protected $fillable = [
        'wallet_id','type','amount','balance_after','payment_id','reference','meta'
    ];

    protected $casts = [
        'amount' => 'decimal:2',
        'balance_after' => 'decimal:2',
        'meta' => 'array'
    ];

    public function wallet() { return $this->belongsTo(Wallet::class); }
    public function payment() { return $this->belongsTo(Payment::class); }
}
