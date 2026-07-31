<?php

namespace App\Models\RH\Payroll;

use App\Models\Concerns\HasAutoCode;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\SoftDeletes;

class PayrollPeriod extends Model
{
    use HasFactory, SoftDeletes, HasAutoCode;

    protected static $codePrefix = 'PAY';

    protected $table = 'payroll_periods';

    protected $fillable = [
        'code',
        'name',
        'start_date',
        'end_date',
        'payment_date',
        'status',
        'notes',
    ];

    protected $casts = [
        'start_date' => 'date',
        'end_date' => 'date',
        'payment_date' => 'date',
    ];

    public function items()
    {
        return $this->hasMany(PayrollItem::class, 'payroll_period_id');
    }
}
