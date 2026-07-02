<?php

namespace App\Models\RH\Benefit;

use App\Models\RH\Employee\Employee;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\SoftDeletes;

class MedicalAssistance extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'medical_assistance';

    protected $fillable = [
        'employee_id', 'assistance_type', 'provider',
        'description', 'amount', 'assistance_date',
        'status', 'notes',
    ];

    protected function casts(): array
    {
        return [
            'assistance_date' => 'date',
        ];
    }

    public function employee()
    {
        return $this->belongsTo(Employee::class);
    }
}
