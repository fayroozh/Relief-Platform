<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PendingDonation extends Model
{
    protected $fillable = [
        'user_id',
        'case_id',
        'project_id',
        'amount',
        'payer_name',
        'phone',
        'note',
        'status'
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function case()
    {
        return $this->belongsTo(CaseModel::class, 'case_id');
    }

    public function project()
    {
        return $this->belongsTo(Project::class, 'project_id');
    }
}
