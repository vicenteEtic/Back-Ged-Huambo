<?php

namespace App\Models\RH\Performance;

use App\Models\RH\Employee\Employee;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\SoftDeletes;

class PerformanceGoal extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'performance_goals';

    protected $fillable = [
        'employee_id', 'cycle_id', 'title', 'description',
        'category', 'weight', 'score', 'notes',
    ];

    public function employee()
    {
        return $this->belongsTo(Employee::class);
    }

    public function cycle()
    {
        return $this->belongsTo(PerformanceCycle::class, 'cycle_id');
    }
}
