<?php

namespace App\Models\RH\Attendance;

use App\Enum\AttendanceRequestStatus;
use App\Models\RH\Employee\Employee;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class AttendanceRequest extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'attendance_requests';

    protected $fillable = [
        'request_number',
        'employee_id',
        'attendance_request_type_id',
        'start_date',
        'end_date',
        'applies_full_day',
        'reason',
        'description',
        'oversight_note',
        'status',
        'benefit_start_date',
        'benefit_until',
        'benefit_active',
        'despacho_number',
        'despacho_path',
        'despacho_decision',
        'requested_by',
        'reviewed_by',
        'decided_by',
        'decided_at',
        'decision_note',
    ];

    protected function casts(): array
    {
        return [
            'start_date' => 'date:Y-m-d',
            'end_date' => 'date:Y-m-d',
            'benefit_start_date' => 'date:Y-m-d',
            'benefit_until' => 'date:Y-m-d',
            'applies_full_day' => 'boolean',
            'benefit_active' => 'boolean',
            'decided_at' => 'datetime',
        ];
    }

    public function employee()
    {
        return $this->belongsTo(Employee::class);
    }

    public function type()
    {
        return $this->belongsTo(AttendanceRequestType::class, 'attendance_request_type_id');
    }

    public function requester()
    {
        return $this->belongsTo(User::class, 'requested_by');
    }

    public function reviewer()
    {
        return $this->belongsTo(User::class, 'reviewed_by');
    }

    public function decidedBy()
    {
        return $this->belongsTo(User::class, 'decided_by');
    }

    public function documents()
    {
        return $this->hasMany(AttendanceRequestDocument::class);
    }

    public function logs()
    {
        return $this->hasMany(AttendanceRequestLog::class)->orderBy('created_at');
    }

    public function attendanceRecords()
    {
        return $this->hasMany(Attendance::class);
    }

    public function isDecided(): bool
    {
        return in_array($this->status, [
            AttendanceRequestStatus::APPROVED,
            AttendanceRequestStatus::REJECTED,
            AttendanceRequestStatus::CANCELLED,
        ], true);
    }
}
