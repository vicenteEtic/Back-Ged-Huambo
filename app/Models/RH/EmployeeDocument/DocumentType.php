<?php

namespace App\Models\RH\EmployeeDocument;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class DocumentType extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'document_types';

    protected $fillable = [
        'code', 'name', 'has_number', 'has_issue_date',
        'has_expiry_date', 'has_place_of_issue', 'description', 'is_active',
    ];

    protected $casts = [
        'has_number' => 'boolean',
        'has_issue_date' => 'boolean',
        'has_expiry_date' => 'boolean',
        'has_place_of_issue' => 'boolean',
        'is_active' => 'boolean',
    ];

    public function documents()
    {
        return $this->hasMany(EmployeeDocument::class);
    }
}
