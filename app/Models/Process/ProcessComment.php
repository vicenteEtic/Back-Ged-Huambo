<?php

namespace App\Models\Process;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class ProcessComment extends Model
{
    use HasFactory;

    protected $table = 'process_comments';

    protected $fillable = [
        'process_id',
        'assignment_id',
        'user_id',
        'comment',
        'comment_type',
        'attachment_path',
    ];

    // === Relações ===

    public function process()
    {
        return $this->belongsTo(Process::class);
    }

    public function assignment()
    {
        return $this->belongsTo(ProcessAssignment::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
