<?php

namespace App\Models\RH\Attendance;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class AttendanceImportLog extends Model
{
    use HasFactory;

    protected $table = 'attendance_import_logs';

    protected $fillable = [
        'filename',
        'total_rows',
        'imported_rows',
        'failed_rows',
        'error_log',
        'imported_by',
    ];

    public function importer()
    {
        return $this->belongsTo(User::class, 'imported_by');
    }
}
