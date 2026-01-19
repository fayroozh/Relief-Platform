<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Project extends Model
{
    use HasFactory;

    protected $fillable = [
        'title',
        'description',
        'goal_amount',
        'raised_amount',
        'deadline',
        'created_by_id',
        'created_by_type',
        'status',
        'image_path',
        'manager_name',
        'category',
        'location',
        'suggested_amounts',
        'payment_channels',
        'thank_you_message',
        'allow_custom_amount',
        'enable_repetition',
        'repetition_type',
        'admin_notes',
    ];

    protected $casts = [
        'suggested_amounts' => 'array',
        'payment_channels' => 'array',
        'allow_custom_amount' => 'boolean',
        'enable_repetition' => 'boolean',
        'goal_amount' => 'decimal:2',
        'raised_amount' => 'decimal:2',
        'deadline' => 'date',
    ];

    // علاقة مع الجمعية
    public function organization()
    {
        return $this->belongsTo(Organization::class, 'created_by_id')->where('created_by_type', 'organization');
    }

    // علاقة مع الأدمن (User)
    public function admin()
    {
        return $this->belongsTo(User::class, 'created_by_id')->where('created_by_type', 'admin');
    }
    public function donations()
    {
        return $this->hasMany(\App\Models\Donation::class);
    }

}
