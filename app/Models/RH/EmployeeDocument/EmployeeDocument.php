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
        'document_type_id',
        'document_type',
        'name',
        'description',
        'file_path',
        'expiry_date',
        'is_lifetime',
        'issue_date',
        'place_of_issue',
        'is_verified',
    ];

    protected $casts = [
        'expiry_date' => 'date',
        'issue_date' => 'date',
        'is_lifetime' => 'boolean',
        'is_verified' => 'boolean',
    ];

    protected $appends = ['expiry_date_display'];

    public function getExpiryDateDisplayAttribute(): ?string
    {
        if ($this->is_lifetime) {
            return 'Vitalício';
        }

        return optional($this->expiry_date)->format('d/m/Y');
    }

    public function employee()
    {
        return $this->belongsTo(Employee::class);
    }

    public function documentType()
    {
        return $this->belongsTo(DocumentType::class);
    }
}
