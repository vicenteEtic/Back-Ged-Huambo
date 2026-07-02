<?php

namespace App\Models\RH\Career;

use App\Models\RH\Employee\Employee;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\SoftDeletes;

class RetirementProcess extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'retirement_processes';

    protected $fillable = [
        'employee_id', 'request_date', 'effective_date',
        'status', 'final_salary', 'pension_amount', 'pension_type',
        'documents', 'approved_by', 'approved_date', 'notes',
    ];

    protected function casts(): array
    {
        return [
            'request_date' => 'date',
            'effective_date' => 'date',
            'approved_date' => 'date',
        ];
    }

    public function employee()
    {
        return $this->belongsTo(Employee::class);
    }

    public function approver()
    {
        return $this->belongsTo(User::class, 'approved_by');
    }

    public function postRetirementHistory()
    {
        return $this->hasMany(PostRetirementHistory::class);
    }
}
