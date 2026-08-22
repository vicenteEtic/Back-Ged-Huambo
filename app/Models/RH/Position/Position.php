<?php

namespace App\Models\RH\Position;

use App\Models\Concerns\HasAutoCode;
use App\Models\RH\Department\Department;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\SoftDeletes;

class Position extends Model
{
    use HasFactory, SoftDeletes, HasAutoCode;

    public const TYPE_CARGO = 'cargo';

    protected static $codePrefix = 'POS';

    protected $table = 'positions';

    protected $fillable = [
        'name',
        'code',
        'type',
        'description',
        'department_id',
        'level',
        'base_salary',
        'transport_allowance',
        'meal_allowance',
        'requirements',
        'is_active',
    ];

    public function department()
    {
        return $this->belongsTo(Department::class);
    }

    public function scopeCargos($query)
    {
        return $query->where('type', self::TYPE_CARGO);
    }
}
