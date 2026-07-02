<?php

namespace App\Models\RH\Payroll;

use App\Models\RH\Employee\Employee;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\SoftDeletes;

class PayrollItem extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'payroll_items';

    protected $fillable = [
        'payroll_period_id',
        'employee_id',
        'base_salary',
        'transport_allowance',
        'meal_allowance',
        'overtime',
        'other_earnings',
        'inss_deduction',
        'irt_deduction',
        'other_deductions',
        'gross_pay',
        'total_deductions',
        'net_pay',
        'status',
        'notes',
    ];

    public function period()
    {
        return $this->belongsTo(PayrollPeriod::class, 'payroll_period_id');
    }

    public function employee()
    {
        return $this->belongsTo(Employee::class);
    }
}
