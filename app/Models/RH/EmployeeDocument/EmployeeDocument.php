<?php

namespace App\Models\RH\EmployeeDocument;

use App\Models\RH\Employee\Employee;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\SoftDeletes;

class EmployeeDocument extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'employee_documents';

    protected $fillable = [
        'employee_id',
        'document_type',
        'name',
        'description',
        'file_path',
        'expiry_date',
        'is_verified',
    ];

    protected $casts = [
        'expiry_date' => 'date',
        'is_verified' => 'boolean',
    ];

    public function employee()
    {
        return $this->belongsTo(Employee::class);
    }
}
