<?php

namespace App\Models\RH\Career;

use App\Models\RH\Employee\Employee;
use App\Models\RH\Position\Position;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\SoftDeletes;

class ProgressionRequest extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'progression_requests';

    protected $fillable = [
        'employee_id', 'rule_id', 'type',
        'from_category', 'to_category',
        'from_position_id', 'to_position_id',
        'current_salary', 'new_salary', 'increase_percent',
        'justification', 'status', 'requested_by', 'effective_date',
    ];

    protected function casts(): array
    {
        return [
            'current_salary' => 'decimal:2',
            'new_salary' => 'decimal:2',
            'increase_percent' => 'decimal:2',
            'effective_date' => 'date',
        ];
    }

    public function employee()
    {
        return $this->belongsTo(Employee::class);
    }

    public function rule()
    {
        return $this->belongsTo(ProgressionRule::class);
    }

    public function fromPosition()
    {
        return $this->belongsTo(Position::class, 'from_position_id');
    }

    public function toPosition()
    {
        return $this->belongsTo(Position::class, 'to_position_id');
    }

    public function requester()
    {
        return $this->belongsTo(User::class, 'requested_by');
    }

    public function approvals()
    {
        return $this->hasMany(ProgressionApproval::class);
    }

    public function latestApproval()
    {
        return $this->hasOne(ProgressionApproval::class)->latestOfMany('level');
    }
}
