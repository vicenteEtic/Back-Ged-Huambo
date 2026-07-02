<?php

namespace App\Models\RH\Career;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class ProgressionApproval extends Model
{
    use HasFactory;

    protected $table = 'progression_approvals';

    protected $fillable = [
        'progression_request_id', 'approver_id',
        'level', 'status', 'comment', 'decided_at',
    ];

    protected function casts(): array
    {
        return [
            'level' => 'integer',
            'decided_at' => 'datetime',
        ];
    }

    public function progressionRequest()
    {
        return $this->belongsTo(ProgressionRequest::class);
    }

    public function approver()
    {
        return $this->belongsTo(User::class, 'approver_id');
    }
}
