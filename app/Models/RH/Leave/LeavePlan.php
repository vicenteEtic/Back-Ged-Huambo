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
        'employee_id', 'year', 'leave_type_id', 'total_days_entitled',
        'days_used', 'days_pending', 'observations', 'created_by',
    ];

    protected function casts(): array
    {
        return [
            'year' => 'integer',
            'total_days_entitled' => 'decimal:1',
            'days_used' => 'decimal:1',
            'days_pending' => 'decimal:1',
        ];
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
