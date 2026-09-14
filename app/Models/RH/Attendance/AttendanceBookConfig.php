<?php

namespace App\Models\RH\Attendance;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class AttendanceBookConfig extends Model
{
    use HasFactory;

    protected $table = 'attendance_book_configs';

    protected $fillable = [
        'attendance_book_departments',
    ];

    protected function casts(): array
    {
        return [
            'attendance_book_departments' => 'array',
        ];
    }
}