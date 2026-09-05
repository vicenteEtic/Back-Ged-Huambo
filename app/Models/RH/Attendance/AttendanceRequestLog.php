<?php

namespace App\Models\RH\Attendance;

use App\Models\User;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class AttendanceRequestLog extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'attendance_request_logs';

    protected $fillable = [
        'attendance_request_id',
        'action',
        'from_status',
        'to_status',
        'note',
        'user_id',
    ];

    public function request()
    {
        return $this->belongsTo(AttendanceRequest::class, 'attendance_request_id');
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
