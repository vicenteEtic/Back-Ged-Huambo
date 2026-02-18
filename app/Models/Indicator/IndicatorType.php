<?php

namespace App\Models\Indicator;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\SoftDeletes;

class IndicatorType extends Model
{
    use HasFactory, SoftDeletes;
    protected $table = 'indicator_type';
    protected $primaryKey = 'id';
    protected $fillable = [
        'description',
        'score',
        'risk',
        'indicator_id',
        'has_beneficial'
    ];

    public function indicator()
    {
        return $this->belongsTo(Indicator::class, 'indicator_id');
    }
}
