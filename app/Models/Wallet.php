<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Wallet extends Model
{
    protected $fillable = [
        'user_id','organization_id','project_id','name','is_system','balance'
    ];

    protected $casts = [
        'is_system' => 'boolean',
        'balance' => 'decimal:2'
    ];

    public function user() { return $this->belongsTo(User::class); }
    public function organization() { return $this->belongsTo(Organization::class); }
    public function project() { return $this->belongsTo(Project::class); }
    public function transactions() { return $this->hasMany(WalletTransaction::class); }

    // Helper: credit wallet
    public function credit(float $amount, $paymentId = null, $reference = null, $meta = []) {
        $this->balance = bcmul(bcadd($this->balance, 0, 2) + 0, 1, 2); // ensure decimal
        $this->increment('balance', $amount);
        $t = $this->transactions()->create([
            'type' => 'credit',
            'amount' => $amount,
            'balance_after' => $this->balance,
            'payment_id' => $paymentId,
            'reference' => $reference,
            'meta' => $meta,
        ]);
        return $t;
    }

    // Helper: debit wallet
    public function debit(float $amount, $paymentId = null, $reference = null, $meta = []) {
        if ($this->balance < $amount) {
            throw new \Exception('Insufficient wallet balance');
        }
        $this->decrement('balance', $amount);
        $t = $this->transactions()->create([
            'type' => 'debit',
            'amount' => $amount,
            'balance_after' => $this->balance,
            'payment_id' => $paymentId,
            'reference' => $reference,
            'meta' => $meta,
        ]);
        return $t;
    }
}
