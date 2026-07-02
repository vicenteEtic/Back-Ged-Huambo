<?php

namespace App\Models\RH\Benefit;

use App\Models\RH\Employee\Employee;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\SoftDeletes;

class EmployeeBenefit extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'employee_benefits';

    protected $fillable = [
        'employee_id', 'benefit_type_id', 'amount',
        'start_date', 'end_date', 'status', 'notes',
    ];

    protected $casts = [
        'start_date' => 'date',
        'end_date' => 'date',
    ];

    public function employee()
    {
        return $this->belongsTo(Employee::class);
    }

    public function benefitType()
    {
        return $this->belongsTo(BenefitType::class);
    }
}
