<?php

namespace App\Models\RH\Department;

use App\Models\Permission\Permission;
use App\Models\RH\Area\Area;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class DepartmentPermission extends Model
{
    use HasFactory;

    protected $table = 'department_permissions';

    protected $fillable = [
        'department_id',
        'area_id',
        'permission_id',
        'granted_by',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
        ];
    }

    public function department()
    {
        return $this->belongsTo(Department::class);
    }

    public function area()
    {
        return $this->belongsTo(Area::class);
    }

    public function permission()
    {
        return $this->belongsTo(Permission::class);
    }

    public function grantedBy()
    {
        return $this->belongsTo(User::class, 'granted_by');
    }
}
