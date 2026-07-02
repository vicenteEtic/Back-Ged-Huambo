<?php

namespace App\Models\RH\Disciplinary;

use App\Models\RH\Employee\Employee;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\SoftDeletes;

class DisciplinaryRecord extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'disciplinary_records';

    protected $fillable = [
        'employee_id', 'disciplinary_type_id', 'occurred_at',
        'description', 'evidence_path', 'status', 'reported_by',
        'resolution', 'sanction', 'sanction_start', 'sanction_end',
    ];

    protected $casts = [
        'occurred_at' => 'date',
        'sanction_start' => 'date',
        'sanction_end' => 'date',
    ];

    public function employee()
    {
        return $this->belongsTo(Employee::class);
    }

    public function disciplinaryType()
    {
        return $this->belongsTo(DisciplinaryType::class);
    }

    public function reporter()
    {
        return $this->belongsTo(User::class, 'reported_by');
    }
}
