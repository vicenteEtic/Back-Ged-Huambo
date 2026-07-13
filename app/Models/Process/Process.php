<?php

namespace App\Models\Process;

use App\Models\RH\Area\Area;
use App\Models\RH\Department\Department;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\SoftDeletes;

class Process extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'processes';

    protected $fillable = [
        'process_type',
        'sequence_number',
        'reception_date',
        'reception_time',
        'reference_number',
        'document_date',
        'subject',
        'notes',
        'sender_entity',
        'file_path',
        'file_type',
        'file_size',
        'mime_type',
        'justification',
        'classification',
        'deadline',
        'status',
        'current_department_id',
        'current_area_id',
        'current_holder_id',
        'origin_department_id',
        'origin_area_id',
        'target_department_id',
        'priority',
        'rejection_reason',
        'closed_at',
        'closed_by',
        'received_by',
        'created_by',
    ];

    protected function casts(): array
    {
        return [
            'reception_date' => 'date',
            'document_date' => 'date',
            'deadline' => 'date',
            'closed_at' => 'datetime',
        ];
    }

    // === Relações ===

    public function currentDepartment()
    {
        return $this->belongsTo(Department::class, 'current_department_id');
    }

    public function currentArea()
    {
        return $this->belongsTo(Area::class, 'current_area_id');
    }

    public function currentHolder()
    {
        return $this->belongsTo(User::class, 'current_holder_id');
    }

    public function originDepartment()
    {
        return $this->belongsTo(Department::class, 'origin_department_id');
    }

    public function originArea()
    {
        return $this->belongsTo(Area::class, 'origin_area_id');
    }

    public function targetDepartment()
    {
        return $this->belongsTo(Department::class, 'target_department_id');
    }

    public function receivedBy()
    {
        return $this->belongsTo(User::class, 'received_by');
    }

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function closedBy()
    {
        return $this->belongsTo(User::class, 'closed_by');
    }

    public function assignments()
    {
        return $this->hasMany(ProcessAssignment::class);
    }

    public function movements()
    {
        return $this->hasMany(ProcessMovement::class);
    }

    public function comments()
    {
        return $this->hasMany(ProcessComment::class);
    }

    public function documents()
    {
        return $this->hasMany(ProcessDocument::class);
    }

    // === Scopes ===

    public function scopeExternal($query)
    {
        return $query->where('process_type', 'external');
    }

    public function scopeInternal($query)
    {
        return $query->where('process_type', 'internal');
    }

    public function scopePending($query)
    {
        return $query->whereNotIn('status', ['closed', 'rejected']);
    }

    public function scopeForDepartment($query, int $departmentId)
    {
        return $query->where('current_department_id', $departmentId);
    }

    public function scopeForUser($query, int $userId)
    {
        return $query->where('current_holder_id', $userId);
    }
}
