<?php

namespace App\Models\RH\FunctionalHistory;

use App\Models\RH\Employee\Employee;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\SoftDeletes;

class FunctionalHistory extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'functional_history';

    protected $fillable = [
        'employee_id',
        'type',
        'previous_value',
        'new_value',
        'effective_date',
        'document_reference',
        'notes',
        'created_by',
    ];

    protected function casts(): array
    {
        return [
            'effective_date' => 'date',
            'previous_value' => 'array',
            'new_value' => 'array',
        ];
    }

    public function employee()
    {
        return $this->belongsTo(Employee::class);
    }

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }
}
