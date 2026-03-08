<?php

namespace App\Models\Entities;

use App\Enum\FormEstablishment;
use App\Enum\StatusAssessment;
use App\Enum\StatusResidence;
use App\Enum\TypeAssessment;
use App\Models\Indicator\IndicatorType;
use App\Models\User\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\SoftDeletes;
use Mockery\Matcher\Type;

class RiskAssessment extends Model
{
    use HasFactory, SoftDeletes;
    protected $table = 'risk_assessment';
    protected $primaryKey = 'id';
    protected $fillable = [
        'identification_capacity',
        'form_establishment',
        'category',
        'status_residence',
        'profession',
        'pep',
        'country_residence',
        'nationality',
        'entity_id',
        'channel',
        'score',
        'user_id',
        'color',
        'risk_level',
        'diligence',
        'type_assessment',
        'status',
        'risk_assessment_control_id',
        "processesReportedAuthoritie",
        "beneficialOwner",
        "santion",
        "id_risk_formula",
        'reassessmentPeriod'
       
     
    ];


    public $casts = [
        'form_establishment' => FormEstablishment::class,
        'status_residence' =>  StatusResidence::class,
        'santion' => 'boolean',
        'processesReportedAuthoritie' => 'boolean',
        'pep'=> 'boolean',
        'is_legal_representative'=> 'boolean',
        'status_residence'=> 'boolean',

    ];

    public function entity()
    {
        return $this->belongsTo(Entities::class, 'entity_id');
    }
    public function user()
    {
        return $this->belongsTo(User::class, 'user_id');
    }
    public function  profession()
    {
        return $this->belongsTo(IndicatorType::class, 'profession');
    }

    public function indetificationCapacity()
    {
        return $this->belongsTo(IndicatorType::class, 'identification_capacity');
    }

    public function channel()
    {
        return $this->belongsTo(IndicatorType::class, 'channel');
    }

    public function countryResidence()
    {
        return $this->belongsTo(IndicatorType::class, 'country_residence');
    }

    public function category()
    {
        return $this->belongsTo(IndicatorType::class, 'category')->withDefault([
            'score' => 0
        ]);
    }

    public function nationlity()
    {
        return $this->belongsTo(IndicatorType::class, 'nationality');
    }

    public function productRisk()
    {
     return $this->hasMany(ProductRisk::class, 'risk_assessment_id');

    }

    public function beneficialOwners()
    {
        return $this->hasMany(BeneficialOwner::class, 'risk_assessment_id', 'id');
    }

    public function riskFormula()
    {
        return $this->belongsTo(RiskFormula::class, 'id_risk_formula', 'id');

    }

      public function beneficial()
    {
        return $this->hasMany(Beneficial::class, 'risk_assessment_id', 'id');
    }

}
