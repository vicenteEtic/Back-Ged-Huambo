<?php

namespace App\Models\RH\Leave;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\SoftDeletes;

class LeaveType extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'leave_types';

    protected $fillable = [
        'name',
        'code',
        'description',
        'default_days',
        'allows_carryover',
        'max_carryover_days',
        'requires_attachment',
        'is_active',
    ];

    protected $casts = [
        'allows_carryover' => 'boolean',
        'requires_attachment' => 'boolean',
        'is_active' => 'boolean',
    ];

    public function requests()
    {
        return $this->hasMany(LeaveRequest::class, 'leave_type_id');
    }
}
