<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Category extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'description',
        'status',
    ];

    // علاقة مع الحالات
    public function cases()
    {
        return $this->hasMany(CaseModel::class); // اذا سميتي الموديل Case
    }
}
