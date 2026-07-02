<?php

namespace App\Models\RH\Attendance;

use App\Models\RH\Employee\Employee;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\SoftDeletes;

class Attendance extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'attendance';

    protected $fillable = [
        'employee_id',
        'shift_id',
        'date',
        'status',
        'check_in',
        'check_out',
        'expected_check_in',
        'expected_check_out',
        'hours_worked',
        'late_minutes',
        'overtime_minutes',
        'absence_type',
        'absence_reason',
        'is_justified',
        'notes',
    ];

    protected function casts(): array
    {
        return [
            'date' => 'date',
            'hours_worked' => 'decimal:2',
            'late_minutes' => 'integer',
            'overtime_minutes' => 'integer',
            'is_justified' => 'boolean',
        ];
    }

    public function employee()
    {
        return $this->belongsTo(Employee::class);
    }

    public function shift()
    {
        return $this->belongsTo(Shift::class);
    }
}
