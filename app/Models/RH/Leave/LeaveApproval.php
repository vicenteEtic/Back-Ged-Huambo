<?php

namespace App\Models\RH\Leave;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class LeaveApproval extends Model
{
    use HasFactory;

    protected $table = 'leave_approvals';

    protected $fillable = [
        'leave_request_id', 'approver_id',
        'level', 'status', 'comment', 'decided_at',
    ];

    protected function casts(): array
    {
        return [
            'level' => 'integer',
            'decided_at' => 'datetime',
        ];
    }

    public function leaveRequest()
    {
        return $this->belongsTo(LeaveRequest::class);
    }

    public function approver()
    {
        return $this->belongsTo(User::class, 'approver_id');
    }
}
