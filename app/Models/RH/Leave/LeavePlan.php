<?php

namespace App\Models\RH\Leave;

use App\Models\RH\Employee\Employee;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\SoftDeletes;

class LeavePlan extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'leave_plans';

    protected $fillable = [
        'employee_id', 'year', 'expected_month', 'leave_type_id', 'total_days_entitled',
        'days_used', 'days_pending', 'observations', 'upcoming_notified_at', 'created_by',
    ];

    protected function casts(): array
    {
        return [
            'year' => 'integer',
            'expected_month' => 'integer',
            'total_days_entitled' => 'decimal:1',
            'days_used' => 'decimal:1',
            'days_pending' => 'decimal:1',
            'upcoming_notified_at' => 'datetime',
        ];
    }

    public const MONTHS = [
        1 => 'Janeiro', 2 => 'Fevereiro', 3 => 'Março', 4 => 'Abril',
        5 => 'Maio', 6 => 'Junho', 7 => 'Julho', 8 => 'Agosto',
        9 => 'Setembro', 10 => 'Outubro', 11 => 'Novembro', 12 => 'Dezembro',
    ];

    public function getExpectedMonthLabelAttribute(): ?string
    {
        return isset($this->expected_month) ? self::MONTHS[$this->expected_month] : null;
    }

    public function employee()
    {
        return $this->belongsTo(Employee::class);
    }

    public function leaveType()
    {
        return $this->belongsTo(LeaveType::class);
    }

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function leaveRequests()
    {
        return $this->hasMany(LeaveRequest::class);
    }

    public function getDaysRemainingAttribute(): float
    {
        return round($this->total_days_entitled - $this->days_used - $this->days_pending, 1);
    }
}
