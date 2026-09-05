<?php

namespace App\Models\RH\Department;

use App\Models\Concerns\HasAutoCode;
use App\Models\RH\Employee\Employee;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Department extends Model
{
    use HasAutoCode, HasFactory, SoftDeletes;

    protected static $codePrefix = 'DEP';

    protected $table = 'departments';

    protected $fillable = [
        'name',
        'type',
        'code',
        'description',
        'responsible_id',
        'parent_id',
        'area_id',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
        ];
    }

    public function responsible()
    {
        return $this->belongsTo(Employee::class, 'responsible_id');
    }

    public function parent()
    {
        return $this->belongsTo(self::class, 'parent_id');
    }

    public function children()
    {
        return $this->hasMany(self::class, 'parent_id');
    }

    public function area()
    {
        return $this->belongsTo(\App\Models\RH\Area\Area::class);
    }

    public function departmentPermissions()
    {
        return $this->hasMany(\App\Models\RH\Department\DepartmentPermission::class);
    }
}
