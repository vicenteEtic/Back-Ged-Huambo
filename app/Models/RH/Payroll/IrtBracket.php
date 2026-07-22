<?php

namespace App\Models\RH\Payroll;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\SoftDeletes;

class IrtBracket extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'irt_brackets';

    protected $fillable = [
        'bracket',
        'min_salary',
        'max_salary',
        'fixed_amount',
        'rate',
        'excess_over',
        'is_exempt',
        'active',
    ];

    protected $casts = [
        'min_salary' => 'decimal:2',
        'max_salary' => 'decimal:2',
        'fixed_amount' => 'decimal:2',
        'rate' => 'decimal:4',
        'excess_over' => 'decimal:2',
        'is_exempt' => 'boolean',
        'active' => 'boolean',
    ];

    public function scopeActive($query)
    {
        return $query->where('active', true);
    }
}
