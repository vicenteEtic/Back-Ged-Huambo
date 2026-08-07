<?php

namespace App\Models\RH\Leave;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Holiday extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'holidays';

    protected $fillable = [
        'name',
        'date',
        'recurrent',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'date' => 'date',
            'recurrent' => 'boolean',
            'is_active' => 'boolean',
        ];
    }
}
