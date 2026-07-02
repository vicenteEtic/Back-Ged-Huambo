<?php

namespace App\Models\RH\Archive;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\SoftDeletes;

class ArchiveDocumentVersion extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'archive_document_versions';

    protected $fillable = [
        'archive_document_id', 'version_number',
        'file_path', 'file_size', 'mime_type', 'notes', 'created_by',
    ];

    protected function casts(): array
    {
        return [
            'version_number' => 'integer',
            'file_size' => 'integer',
        ];
    }

    public function document()
    {
        return $this->belongsTo(ArchiveDocument::class, 'archive_document_id');
    }

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }
}
