<?php

namespace App\Models\RH\Performance;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class EvaluationScore extends Model
{
    use HasFactory;

    protected $table = 'evaluation_scores';

    protected $fillable = [
        'evaluation_id', 'criterion_id', 'score', 'comment',
    ];

    protected function casts(): array
    {
        return [
            'score' => 'decimal:2',
        ];
    }

    public function evaluation()
    {
        return $this->belongsTo(PerformanceEvaluation::class);
    }

    public function criterion()
    {
        return $this->belongsTo(EvaluationCriterion::class);
    }
}
