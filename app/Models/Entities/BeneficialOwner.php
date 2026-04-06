<?php

namespace App\Models\Entities;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\SoftDeletes;

class BeneficialOwner extends Model
{
    use HasFactory, SoftDeletes;
    protected $table = 'beneficial_owner';
    protected $primaryKey = 'id';
    public $casts = [

        'is_legal_representative' => 'boolean',
        'santion' => 'boolean',
        'pep' => 'boolean',
    ];

    protected $fillable = [
        'name',
        'pep',
        'risk_assessment_id',
        'percentage',
        'is_legal_representative',
        'nationality',
        'santion'
    ];
}
