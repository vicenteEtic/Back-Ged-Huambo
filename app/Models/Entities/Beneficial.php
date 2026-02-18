<?php

namespace App\Models\Entities;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Beneficial extends Model
{
    use HasFactory;
    protected $table = 'beneficial';
    protected $primaryKey = 'id';
    protected $fillable = ['name', 'risk_assessment_id', 'nationality', 'is_pep', 'is_sanctioned', 'processesReportedAuthoritie'];
}