<?php

namespace App\Models\RH\Employee;

use App\Models\RH\Department\Department;
use App\Models\RH\Attendance\Attendance;
use App\Models\RH\Attendance\ShiftAssignment;
use App\Models\RH\FunctionalHistory\FunctionalHistory;
use App\Models\RH\Leave\LeavePlan;
use App\Models\RH\Performance\PerformanceEvaluation;
use App\Models\RH\Position\Position;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\SoftDeletes;

class Employee extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'employees';

    protected static function boot(): void
    {
        parent::boot();

        static::creating(function (Employee $employee) {
            if (empty($employee->employee_number)) {
                $employee->employee_number = self::generateEmployeeNumber();
            }
        });
    }

    public static function generateEmployeeNumber(): string
    {
        $last = static::withTrashed()
            ->where('employee_number', 'like', 'EMP-%')
            ->orderByRaw("CAST(SUBSTRING(employee_number, 5) AS UNSIGNED) DESC")
            ->first();

        if ($last && preg_match('/EMP-(\d+)/', $last->employee_number, $matches)) {
            $next = (int) $matches[1] + 1;
        } else {
            $next = 1;
        }

        return 'EMP-' . str_pad($next, 5, '0', STR_PAD_LEFT);
    }

    protected function casts(): array
    {
        return [
            'hire_date' => 'date',
            'effective_date' => 'date',
            'institution_entry_date' => 'date',
            'date_of_birth' => 'date',
        ];
    }

    protected $fillable = [
        'user_id',
        'employee_number',
        'full_name',
        'date_of_birth',
        'gender',
        'marital_status',
        'nationality',
        'document_type',
        'document_number',
        'nif',
        'personal_email',
        'phone',
        'address',
        'department_id',
        'position_id',
        'hire_date',
        'effective_date',
        'contract_type',
        'base_salary',
        'bank_name',
        'bank_iban',
        'status',
        'photo_url',
        'institution_entry_date',
        'category',
        'career_regime',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function department()
    {
        return $this->belongsTo(Department::class);
    }

    public function position()
    {
        return $this->belongsTo(Position::class);
    }

    public function functionalHistory()
    {
        return $this->hasMany(FunctionalHistory::class);
    }

    public function evaluations()
    {
        return $this->hasMany(PerformanceEvaluation::class);
    }

    public function leavePlans()
    {
        return $this->hasMany(LeavePlan::class);
    }

    public function attendance()
    {
        return $this->hasMany(Attendance::class);
    }

    public function shiftAssignments()
    {
        return $this->hasMany(ShiftAssignment::class);
    }

    public function documents()
    {
        return $this->hasMany(\App\Models\RH\EmployeeDocument\EmployeeDocument::class);
    }
}
