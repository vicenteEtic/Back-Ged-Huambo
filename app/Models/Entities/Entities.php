<?php

namespace App\Models\Entities;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\SoftDeletes;

class Entities extends Model
{
    use HasFactory, SoftDeletes;
    protected $table = 'entities';
    protected $primaryKey = 'id';
    protected $fillable = [
        'social_denomination',
        'policy_number',
        'customer_number',
        'entity_type',
        'color',
        'risk_level',
        'diligence',
        'last_evaluation',
        'deleted_at',
        'created_at',
        'updated_at',
        'nif',
        'reassessmentPeriod'
      
    ];

    public function riskAssessments()
    {
        return $this->hasMany(RiskAssessment::class, 'entity_id');
    }
    public function products()
    {
        return $this->hasMany(ProductRisk::class, 'entity_id');
    }
    
    public function alerts()
{
    return $this->hasMany(\App\Models\Alert\Alert::class, 'entity_id', 'id');
}



}
