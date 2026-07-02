<?php

namespace App\Models\RH\Benefit;

use App\Models\RH\Employee\Employee;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\SoftDeletes;

class BenefitClaim extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'benefit_claims';

    protected $fillable = [
        'employee_id', 'benefit_type_id', 'amount_requested',
        'amount_approved', 'description', 'status',
        'requested_date', 'approved_date', 'approved_by', 'notes',
    ];

    protected function casts(): array
    {
        return [
            'requested_date' => 'date',
            'approved_date' => 'date',
        ];
    }

    public function employee()
    {
        return $this->belongsTo(Employee::class);
    }

    public function benefitType()
    {
        return $this->belongsTo(BenefitType::class);
    }

    public function approver()
    {
        return $this->belongsTo(User::class, 'approved_by');
    }
}
