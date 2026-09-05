<?php

namespace App\Models\RH\Area;

use App\Models\Concerns\HasAutoCode;
use App\Models\RH\Department\Department;
use App\Models\RH\Employee\Employee;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Area extends Model
{
    use HasAutoCode, HasFactory, SoftDeletes;

    protected static $codePrefix = 'ARE';

    protected $table = 'areas';

    protected $fillable = [
        'name',
        'code',
        'description',
        'responsible_id',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
        ];
    }

    public function departments()
    {
        return $this->hasMany(Department::class);
    }

    public function responsible()
    {
        return $this->belongsTo(Employee::class, 'responsible_id');
    }

    public function assignments()
    {
        return $this->hasMany(\App\Models\Process\ProcessAssignment::class);
    }
}
