<?php

namespace App\Models\Process;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\SoftDeletes;

class ProcessDocument extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'process_documents';

    protected $fillable = [
        'process_id',
        'document_type',
        'name',
        'description',
        'file_path',
        'file_type',
        'file_size',
        'mime_type',
        'uploaded_by',
    ];

    protected function casts(): array
    {
        return [
            'file_size' => 'integer',
        ];
    }

    public function process()
    {
        return $this->belongsTo(Process::class);
    }

    public function uploader()
    {
        return $this->belongsTo(User::class, 'uploaded_by');
    }
}
