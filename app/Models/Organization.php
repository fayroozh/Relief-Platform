<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Organization extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'name',
        'slug',
        'description',
        'documents',
        'email',
        'phone',
        'website',
        'status',
        'admin_notes',
        'approved_at',
    ];

    protected $casts = [
        'documents' => 'array',
        'approved_at' => 'datetime',
    ];

    // العلاقات
    public function user()
    {
        return $this->belongsTo(\App\Models\User::class);
    }
}
