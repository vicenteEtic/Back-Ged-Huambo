<?php

namespace App\Models\KYT;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class kytrules extends Model
{
    use HasFactory;
    protected $table = 'kyt_rules';
    protected $primaryKey = 'id';
    protected $fillable = [
        'code',
        'name',
        'active',
        'severity',
        'score',
        'parameters',
    ];
    protected $casts = [
        'parameters' => 'array',
        'active' => 'boolean',
    ];
}
