<?php

namespace App\Models\RH\Department;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\SoftDeletes;

class Department extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'departments';

    protected $fillable = [
        'name',
        'type',
        'code',
        'description',
        'responsible_id',
        'parent_id',
        'is_active',
    ];

    public function responsible()
    {
        return $this->belongsTo(User::class, 'responsible_id');
    }

    public function parent()
    {
        return $this->belongsTo(self::class, 'parent_id');
    }

    public function children()
    {
        return $this->hasMany(self::class, 'parent_id');
    }

    public function areas()
    {
        return $this->hasMany(\App\Models\RH\Area\Area::class);
    }

    public function departmentPermissions()
    {
        return $this->hasMany(\App\Models\RH\Department\DepartmentPermission::class);
    }
}
