<?php

namespace App\Models\RH\Career;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\SoftDeletes;

class ProgressionRule extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'progression_rules';

    protected $fillable = [
        'name', 'code', 'type', 'description',
        'min_months_in_category', 'min_performance_score',
        'requires_training', 'requires_evaluation',
        'from_category', 'to_category',
        'from_level', 'to_level',
        'salary_increase_percent', 'is_active',
    ];

    protected function casts(): array
    {
        return [
            'min_months_in_category' => 'integer',
            'min_performance_score' => 'decimal:2',
            'requires_training' => 'boolean',
            'requires_evaluation' => 'boolean',
            'salary_increase_percent' => 'decimal:2',
            'is_active' => 'boolean',
        ];
    }
}
