<?php

namespace App\Models\RH\Employee;

use App\Models\RH\Department\Department;
use App\Models\RH\Attendance\Attendance;
use App\Models\RH\Category\Category;
use App\Models\RH\FunctionalHistory\FunctionalHistory;
use App\Models\RH\Leave\LeavePlan;
use App\Models\RH\Performance\PerformanceEvaluation;
use App\Models\RH\Position\Position;
use App\Models\User;
use App\Support\FrontUrl;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\SoftDeletes;

class Employee extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'employees';

    public function getPhotoUrlAttribute($value): ?string
    {
        if (empty($value)) {
            return null;
        }

        if (filter_var($value, FILTER_VALIDATE_URL)) {
            return $value;
        }

        // Aceita valores com ou sem prefixo 'storage/' (padrão dos documentos)
        $path = preg_replace('#^/?storage/#', '', $value);

        return FrontUrl::base().'/storage/'.$path;
    }

    protected function casts(): array
    {
        return [
            'hire_date' => 'date',
            'effective_date' => 'date',
            'institution_entry_date' => 'date',
            'date_of_birth' => 'date',
            'category' => 'integer',
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

    public function careerCategory()
    {
        return $this->belongsTo(Category::class, 'category');
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

    public function documents()
    {
        return $this->hasMany(\App\Models\RH\EmployeeDocument\EmployeeDocument::class);
    }
}
