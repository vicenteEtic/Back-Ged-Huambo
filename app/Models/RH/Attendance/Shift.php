<?php

namespace App\Models\RH\Attendance;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\SoftDeletes;

class Shift extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'shifts';

    protected $fillable = [
        'name', 'code', 'start_time', 'end_time',
        'grace_minutes', 'duration_hours', 'description', 'is_active',
    ];

    protected function casts(): array
    {
        return [
            'grace_minutes' => 'integer',
            'duration_hours' => 'decimal:2',
            'is_active' => 'boolean',
        ];
    }
}
