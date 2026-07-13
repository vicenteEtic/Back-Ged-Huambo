<?php

namespace App\Models\Process;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\SoftDeletes;

class ProcessAssignmentTechnician extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'process_assignment_technicians';

    protected $fillable = [
        'process_assignment_id',
        'user_id',
        'assigned_by',
        'status',
        'notes',
        'file_path',
        'file_type',
        'file_size',
        'mime_type',
        'started_at',
        'submitted_at',
        'assigned_at',
    ];

    protected function casts(): array
    {
        return [
            'started_at' => 'datetime',
            'submitted_at' => 'datetime',
            'assigned_at' => 'datetime',
        ];
    }

    // === Relações ===

    public function assignment()
    {
        return $this->belongsTo(ProcessAssignment::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function assignedByUser()
    {
        return $this->belongsTo(User::class, 'assigned_by');
    }
}
