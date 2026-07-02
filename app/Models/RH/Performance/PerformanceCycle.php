<?php

namespace App\Models\RH\Performance;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\SoftDeletes;

class PerformanceCycle extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'performance_cycles';

    protected $fillable = [
        'name', 'code', 'start_date', 'end_date', 'status',
    ];

    protected $casts = [
        'start_date' => 'date',
        'end_date' => 'date',
    ];
}
