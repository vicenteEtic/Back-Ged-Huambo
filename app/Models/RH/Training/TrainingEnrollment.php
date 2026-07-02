<?php

namespace App\Models\RH\Training;

use App\Models\RH\Employee\Employee;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\SoftDeletes;

class TrainingEnrollment extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'training_enrollments';

    protected $fillable = [
        'session_id', 'employee_id', 'status', 'grade', 'notes',
    ];

    public function session()
    {
        return $this->belongsTo(TrainingSession::class, 'session_id');
    }

    public function employee()
    {
        return $this->belongsTo(Employee::class);
    }

    public function certificate()
    {
        return $this->hasOne(TrainingCertificate::class, 'enrollment_id');
    }
}
