<?php

namespace App\Models\RH\Disciplinary;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\SoftDeletes;

class DisciplinaryType extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'disciplinary_types';

    protected $fillable = [
        'name', 'code', 'description', 'severity', 'is_active',
    ];

    protected $casts = ['is_active' => 'boolean'];
}
