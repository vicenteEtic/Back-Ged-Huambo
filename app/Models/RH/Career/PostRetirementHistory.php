<?php

namespace App\Models\RH\Career;

use App\Models\RH\Employee\Employee;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\SoftDeletes;

class PostRetirementHistory extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'post_retirement_history';

    protected $fillable = [
        'employee_id', 'retirement_process_id', 'record_date',
        'type', 'description', 'amount', 'notes',
    ];

    protected function casts(): array
    {
        return [
            'record_date' => 'date',
        ];
    }

    public function employee()
    {
        return $this->belongsTo(Employee::class);
    }

    public function retirementProcess()
    {
        return $this->belongsTo(RetirementProcess::class);
    }
}
