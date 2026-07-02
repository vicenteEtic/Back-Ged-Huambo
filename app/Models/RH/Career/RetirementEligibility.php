<?php

namespace App\Models\RH\Career;

use App\Models\RH\Employee\Employee;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\SoftDeletes;

class RetirementEligibility extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'retirement_eligibility';

    protected $fillable = [
        'employee_id', 'retirement_age', 'contribution_years',
        'minimum_contribution_years', 'age_eligible',
        'contribution_eligible', 'expected_retirement_date', 'observations',
    ];

    protected function casts(): array
    {
        return [
            'age_eligible' => 'boolean',
            'contribution_eligible' => 'boolean',
            'expected_retirement_date' => 'date',
        ];
    }

    public function employee()
    {
        return $this->belongsTo(Employee::class);
    }
}
