<?php

namespace App\Models\Diligence;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\SoftDeletes;

class Diligence extends Model
{
    use HasFactory, SoftDeletes;
    protected $table = 'diligence';
    protected $primaryKey = 'id';
    protected $fillable = [
        'name',
        'description',
        'max',
        'min',
        'risk',
        'color',
        'deleted_at',
        'created_at',
        'updated_at',
        'reassessmentPeriod'
    ];
}
