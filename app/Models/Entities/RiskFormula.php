<?php

namespace App\Models\Entities;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class RiskFormula extends Model
{
    use HasFactory;
    protected $table = 'risk_formula';
    protected $primaryKey = 'id';
    protected $fillable = [
        'name',
        'identification_capacity',
        'form_establishment',
        'category',
        'status_residence',
        'profession',
        'pep',
        'country_residence',
        'nationality',
        'entity_type',
        'channel',
        'product_risk',
        'santion',
        'distributionChannel',
        'beneficialOwner',
        'processesReportedAuthoritie'
    ];


	protected $casts = [

	];
    
}