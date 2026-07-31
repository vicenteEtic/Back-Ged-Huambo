<?php

namespace App\Models\Process;

use App\Models\RH\Area\Area;
use App\Models\RH\Department\Department;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\SoftDeletes;

class ProcessAssignment extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'process_assignments';

    protected $fillable = [
        'process_id',
        'department_id',
        'area_id',
        'assigned_by',
        'visibility',
        'status',
        'priority',
        'deadline',
        'notes',
        'result_notes',
        'result_file_path',
        'result_file_type',
        'result_file_size',
        'result_mime_type',
        'started_at',
        'completed_at',
        'validated_at',
        'validated_by',
    ];

    protected function casts(): array
    {
        return [
            'deadline' => 'date',
            'started_at' => 'datetime',
            'completed_at' => 'datetime',
            'validated_at' => 'datetime',
        ];
    }

    // === Relações ===

    public function process()
    {
        return $this->belongsTo(Process::class);
    }

    public function department()
    {
        return $this->belongsTo(Department::class);
    }

    public function area()
    {
        return $this->belongsTo(Area::class);
    }

    public function assignedBy()
    {
        return $this->belongsTo(User::class, 'assigned_by');
    }

    public function validatedBy()
    {
        return $this->belongsTo(User::class, 'validated_by');
    }

    public function technicians()
    {
        return $this->hasMany(ProcessAssignmentTechnician::class);
    }

    public function comments()
    {
        return $this->hasMany(ProcessComment::class, 'assignment_id');
    }

    // === Helpers ===

    public function isPublic(): bool
    {
        return $this->visibility === 'public';
    }

    public function allTechniciansSubmitted(): bool
    {
        return $this->technicians->isNotEmpty()
            && $this->technicians->every(
                fn ($t) => in_array($t->status, ['submitted', 'validated'])
            );
    }
}
