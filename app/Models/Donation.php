<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Donation extends Model
{
    use HasFactory;

    protected $fillable = [
        'case_id',
        'user_id',
        'amount',
        'method',
        'status',
        'note',
        'receipt_path',
    ];

    // علاقة مع الحالة
    public function case()
    {
        return $this->belongsTo(CaseModel::class, 'case_id');
    }

    // علاقة مع المتبرع (المستخدم)
    public function donor()
    {
        return $this->belongsTo(User::class, 'user_id');
    }
}
