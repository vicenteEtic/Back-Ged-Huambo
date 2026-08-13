<?php

namespace App\Models\RH\Attendance;

use App\Models\RH\Employee\Employee;
use App\Models\User;
use App\Support\FrontUrl;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class AbsenceJustification extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'absence_justifications';

    protected $fillable = [
        'attendance_id',
        'employee_id',
        'date',
        'absence_type',
        'reason',
        'proof_path',
        'status',
        'submitted_by',
        'reviewed_by',
        'reviewed_at',
        'review_notes',
    ];

    protected function casts(): array
    {
        return [
            'date' => 'date:Y-m-d',
            'reviewed_at' => 'datetime',
        ];
    }

    public function getProofUrlAttribute($value): ?string
    {
        if (empty($this->proof_path)) {
            return null;
        }

        if (filter_var($this->proof_path, FILTER_VALIDATE_URL)) {
            return $this->proof_path;
        }

        return FrontUrl::base().'/storage/'.$this->proof_path;
    }

    public function attendance()
    {
        return $this->belongsTo(Attendance::class);
    }

    public function employee()
    {
        return $this->belongsTo(Employee::class);
    }

    public function submittedBy()
    {
        return $this->belongsTo(User::class, 'submitted_by');
    }

    public function reviewedBy()
    {
        return $this->belongsTo(User::class, 'reviewed_by');
    }
}
