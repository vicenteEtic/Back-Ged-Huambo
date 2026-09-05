<?php

namespace App\Models\RH\Attendance;

use App\Models\User;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class AttendanceRequestDocument extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'attendance_request_documents';

    protected $fillable = [
        'attendance_request_id',
        'document_type',
        'original_name',
        'file_path',
        'mime_type',
        'size',
        'uploaded_by',
    ];

    public function request()
    {
        return $this->belongsTo(AttendanceRequest::class, 'attendance_request_id');
    }

    public function uploader()
    {
        return $this->belongsTo(User::class, 'uploaded_by');
    }
}
