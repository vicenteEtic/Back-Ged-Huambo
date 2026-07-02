<?php

namespace App\Models\RH\Performance;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\SoftDeletes;

class EvaluationCriterion extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'evaluation_criteria';

    protected $fillable = [
        'cycle_id', 'name', 'description', 'section',
        'weight', 'max_score', 'is_active',
    ];

    protected function casts(): array
    {
        return [
            'weight' => 'decimal:2',
            'max_score' => 'integer',
            'is_active' => 'boolean',
        ];
    }

    public function cycle()
    {
        return $this->belongsTo(PerformanceCycle::class);
    }
}
