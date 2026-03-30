<?php

namespace App\Models\Alert;

use App\Models\Entities\Entities;
use App\Models\Entities\RiskAssessment;
use App\Models\User\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Alert extends Model
{
    use HasFactory;
    protected $table = 'alert';
    protected $primaryKey = 'id';
    protected $casts = [
        'alert_priority' => 'boolean',
    ];
    protected $fillable = [
        'description',
        'name',
        'level',
        'origin_id',
        'entity_id',
        'from_id',
        'score',
        'type',
        'list',
        'is_active',
        'country',
        'birth_date',
        'is_pep',
        'is_sanctioned',
        'is_reported',
        'category',
        'assigned_to',
        'risk_assessment_id',
        'alert_priority'


    ];


    public function entity()
    {
        return $this->belongsTo(Entities::class, 'entity_id');
    }

    public function assigned()
    {
        return $this->belongsTo(User::class, 'assigned_to');
    }

    public function riskAssessment()
    {
        return $this->belongsTo(RiskAssessment::class, 'risk_assessment_id');
    }
    

    public function users()
    {
        return $this->belongsToMany(User::class, 'alert_user')
            ->withPivot('is_read')
            ->withTimestamps();
    }
}
