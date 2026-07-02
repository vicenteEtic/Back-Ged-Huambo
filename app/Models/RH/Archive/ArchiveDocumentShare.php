<?php

namespace App\Models\RH\Archive;

use App\Models\RH\Employee\Employee;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\SoftDeletes;

class ArchiveDocumentShare extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'archive_document_shares';

    protected $fillable = [
        'archive_document_id', 'shared_with_user_id',
        'shared_with_employee_id', 'permission',
        'expires_at', 'shared_by',
    ];

    protected function casts(): array
    {
        return [
            'expires_at' => 'datetime',
        ];
    }

    public function document()
    {
        return $this->belongsTo(ArchiveDocument::class, 'archive_document_id');
    }

    public function sharedWithUser()
    {
        return $this->belongsTo(User::class, 'shared_with_user_id');
    }

    public function sharedWithEmployee()
    {
        return $this->belongsTo(Employee::class, 'shared_with_employee_id');
    }

    public function sharer()
    {
        return $this->belongsTo(User::class, 'shared_by');
    }
}
