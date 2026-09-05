<?php

namespace App\Models\RH\Attendance;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class AttendanceRequestType extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'attendance_request_types';

    protected $fillable = [
        'code',
        'name',
        'description',
        'required_documents',
        'max_days',
        'legal_ref',
        'is_active',
        'sort_order',
    ];

    protected function casts(): array
    {
        return [
            'required_documents' => 'array',
            'max_days' => 'integer',
            'is_active' => 'boolean',
            'sort_order' => 'integer',
        ];
    }

    public function requests()
    {
        return $this->hasMany(AttendanceRequest::class, 'attendance_request_type_id');
    }
}
