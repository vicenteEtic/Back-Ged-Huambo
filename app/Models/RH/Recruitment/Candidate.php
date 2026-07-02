<?php

namespace App\Models\RH\Recruitment;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\SoftDeletes;

class Candidate extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'candidates';

    protected $fillable = [
        'full_name', 'email', 'phone', 'document_type',
        'document_number', 'address', 'resume_path',
        'source', 'notes',
    ];

    public function applications()
    {
        return $this->hasMany(Application::class);
    }
}
