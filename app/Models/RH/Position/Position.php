<?php

namespace App\Models\RH\Position;

use App\Models\Concerns\HasAutoCode;
use App\Models\RH\Department\Department;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Position extends Model
{
    use HasFactory, SoftDeletes, HasAutoCode;

    public const TYPE_CARGO = 'cargo';

    protected static $codePrefix = 'POS';

    protected $table = 'positions';

    /**
     * Cargos guardam apenas o nome — o código é gerado automaticamente.
     * Salários, subsídios e departamento pertencem ao funcionário/categoria.
     */
    protected $fillable = [
        'name',
        'code',
        'type',
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
