<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Payment extends Model
{
    protected $fillable = [
        'user_id','organization_id','project_id','case_id',
        'amount','currency','method','status',
        'platform_fee','points_share','system_share','net_amount','meta'
    ];

    protected $casts = [
        'meta' => 'array',
        'amount' => 'decimal:2',
        'platform_fee' => 'decimal:2',
        'points_share' => 'decimal:2',
        'system_share' => 'decimal:2',
        'net_amount' => 'decimal:2',
    ];

    public function user() { return $this->belongsTo(User::class); }
    public function organization() { return $this->belongsTo(Organization::class); }
    public function walletTransactions() { return $this->hasMany(WalletTransaction::class); }
}
