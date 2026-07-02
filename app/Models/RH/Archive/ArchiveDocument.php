<?php

namespace App\Models\RH\Archive;

use App\Models\RH\Employee\Employee;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\SoftDeletes;

class ArchiveDocument extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'archive_documents';

    protected $fillable = [
        'category_id', 'employee_id', 'title', 'description',
        'document_number', 'reference_number', 'issuing_authority',
        'file_path', 'file_type', 'file_size', 'mime_type',
        'status', 'confidentiality', 'metadata',
        'issued_date', 'expiry_date',
        'is_physical_copy', 'physical_location',
        'approved_by', 'approved_at', 'tags', 'created_by',
    ];

    protected function casts(): array
    {
        return [
            'metadata' => 'array',
            'tags' => 'array',
            'file_size' => 'integer',
            'is_physical_copy' => 'boolean',
            'issued_date' => 'date',
            'expiry_date' => 'date',
            'approved_at' => 'datetime',
        ];
    }

    public function category()
    {
        return $this->belongsTo(ArchiveCategory::class, 'category_id');
    }

    public function employee()
    {
        return $this->belongsTo(Employee::class);
    }

    public function versions()
    {
        return $this->hasMany(ArchiveDocumentVersion::class, 'archive_document_id');
    }

    public function shares()
    {
        return $this->hasMany(ArchiveDocumentShare::class, 'archive_document_id');
    }

    public function approver()
    {
        return $this->belongsTo(User::class, 'approved_by');
    }

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }
}
