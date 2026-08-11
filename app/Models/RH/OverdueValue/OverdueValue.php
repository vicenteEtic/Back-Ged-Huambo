<?php

namespace App\Models\RH\OverdueValue;

use App\Enum\OverdueValueStatus;
use App\Enum\OverdueValueType;
use App\Models\RH\Employee\Employee;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class OverdueValue extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'overdue_values';

    protected $fillable = [
        'employee_id',
        'type',
        'description',
        'amount',
        'paid_amount',
        'status',
        'due_date',
        'settled_date',
        'reference_number',
        'notes',
        'recorded_by',
    ];

    protected function casts(): array
    {
        return [
            'amount' => 'decimal:2',
            'paid_amount' => 'decimal:2',
            'due_date' => 'date',
            'settled_date' => 'date',
            'type' => OverdueValueType::class,
            'status' => OverdueValueStatus::class,
        ];
    }

    public function getTypeLabelAttribute(): string
    {
        return $this->type?->label() ?? $this->type;
    }

    public function getStatusLabelAttribute(): string
    {
        return $this->status?->label() ?? $this->status;
    }

    public function getRemainingAmountAttribute(): float
    {
        return (float) $this->amount - (float) $this->paid_amount;
    }

    public function toArray(): array
    {
        $data = parent::toArray();
        $data['type_label'] = $this->type_label;
        $data['status_label'] = $this->status_label;
        $data['remaining_amount'] = $this->remaining_amount;

        return $data;
    }

    public function employee()
    {
        return $this->belongsTo(Employee::class);
    }

    public function recorder()
    {
        return $this->belongsTo(User::class, 'recorded_by');
    }
}
