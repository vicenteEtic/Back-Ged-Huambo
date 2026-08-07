<?php

namespace App\Models\RH\Leave;

use App\Models\Concerns\HasAutoCode;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\SoftDeletes;

class LeaveType extends Model
{
    use HasFactory, SoftDeletes, HasAutoCode;

    protected static $codePrefix = 'LVT';

    protected $table = 'leave_types';

    protected $fillable = [
        'name',
        'code',
        'description',
        'default_days',
        'service_years_based',
        'allows_carryover',
        'max_carryover_days',
        'requires_attachment',
        'is_active',
    ];

    protected $casts = [
        'allows_carryover' => 'boolean',
        'service_years_based' => 'boolean',
        'requires_attachment' => 'boolean',
        'is_active' => 'boolean',
    ];

    public function requests()
    {
        return $this->hasMany(LeaveRequest::class, 'leave_type_id');
    }
}
