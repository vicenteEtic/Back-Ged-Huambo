<?php

namespace App\Models\RH\Payroll;

use App\Models\RH\Employee\Employee;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\SoftDeletes;

class Payslip extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'payslips';

    protected $fillable = [
        'employee_id', 'payroll_period_id', 'payslip_number',
        'base_salary', 'transport_allowance', 'meal_allowance',
        'overtime', 'other_earnings', 'gross_pay',
        'inss_deduction', 'irt_deduction', 'other_deductions',
        'total_deductions', 'net_pay', 'payment_date',
        'status', 'generated_at', 'downloaded_at',
    ];

    protected function casts(): array
    {
        return [
            'payment_date' => 'date',
            'generated_at' => 'datetime',
            'downloaded_at' => 'datetime',
        ];
    }

    public function employee()
    {
        return $this->belongsTo(Employee::class);
    }

    public function period()
    {
        return $this->belongsTo(PayrollPeriod::class, 'payroll_period_id');
    }
}
