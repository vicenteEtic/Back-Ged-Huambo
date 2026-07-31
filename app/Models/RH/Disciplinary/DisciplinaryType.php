<?php

namespace App\Models\RH\Disciplinary;

use App\Models\Concerns\HasAutoCode;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\SoftDeletes;

class DisciplinaryType extends Model
{
    use HasFactory, SoftDeletes, HasAutoCode;

    protected static $codePrefix = 'DSC';

    protected $table = 'disciplinary_types';

    protected $fillable = [
        'name', 'code', 'description', 'severity', 'is_active',
    ];

    protected $casts = ['is_active' => 'boolean'];
}
