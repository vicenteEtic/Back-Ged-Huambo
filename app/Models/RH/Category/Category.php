<?php

namespace App\Models\RH\Category;

use App\Models\Concerns\HasAutoCode;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Category extends Model
{
    use HasFactory, SoftDeletes, HasAutoCode;

    protected static $codePrefix = 'CAT';

    protected $table = 'categories';

    protected $fillable = [
        'name',
        'code',
        'group',
        'level',
        'base_salary',
        'description',
        'is_active',
    ];

    protected $casts = [
        'base_salary' => 'float',
        'level' => 'integer',
        'is_active' => 'boolean',
    ];
}
