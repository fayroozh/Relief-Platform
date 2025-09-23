<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PointTransaction extends Model
{
    protected $fillable = [
        'user_id','points','type','source','payment_id','meta'
    ];

    protected $casts = [
        'meta' => 'array'
    ];

    public function user() { return $this->belongsTo(User::class); }
    public function payment() { return $this->belongsTo(Payment::class); }
}
