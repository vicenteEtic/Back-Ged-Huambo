<?php

namespace App\Models\Process;

use App\Models\RH\Area\Area;
use App\Models\RH\Department\Department;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class ProcessMovement extends Model
{
    use HasFactory;

    protected $table = 'process_movements';

    protected $fillable = [
        'process_id',
        'from_department_id',
        'from_area_id',
        'to_department_id',
        'to_area_id',
        'from_user_id',
        'to_user_id',
        'movement_type',
        'notes',
        'attachment_path',
    ];

    // === Relações ===

    public function process()
    {
        return $this->belongsTo(Process::class);
    }

    public function fromDepartment()
    {
        return $this->belongsTo(Department::class, 'from_department_id');
    }

    public function fromArea()
    {
        return $this->belongsTo(Area::class, 'from_area_id');
    }

    public function toDepartment()
    {
        return $this->belongsTo(Department::class, 'to_department_id');
    }

    public function toArea()
    {
        return $this->belongsTo(Area::class, 'to_area_id');
    }

    public function fromUser()
    {
        return $this->belongsTo(User::class, 'from_user_id');
    }

    public function toUser()
    {
        return $this->belongsTo(User::class, 'to_user_id');
    }
}
