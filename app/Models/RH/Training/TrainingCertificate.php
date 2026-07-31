<?php

namespace App\Models\RH\Training;

use App\Models\RH\Employee\Employee;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class TrainingCertificate extends Model
{
    use HasFactory;

    protected $table = 'training_certificates';

    protected $fillable = [
        'enrollment_id', 'certificate_number', 'issued_at',
        'expiry_date', 'file_path',
    ];

    protected $casts = [
        'issued_at' => 'date',
        'expiry_date' => 'date',
    ];

    public function enrollment()
    {
        return $this->belongsTo(TrainingEnrollment::class, 'enrollment_id');
    }

    public function trainingEnrollment()
    {
        return $this->belongsTo(TrainingEnrollment::class, 'enrollment_id');
    }

    public function employee()
    {
        return $this->hasOneThrough(
            Employee::class,
            TrainingEnrollment::class,
            'id',
            'id',
            'enrollment_id',
            'employee_id'
        );
    }

    public function session()
    {
        return $this->hasOneThrough(
            TrainingSession::class,
            TrainingEnrollment::class,
            'id',
            'id',
            'enrollment_id',
            'session_id'
        );
    }
}
