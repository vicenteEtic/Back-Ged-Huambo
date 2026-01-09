<?php

namespace App\Models\AmlAlert;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class AmlAlert extends Model
{
    use HasFactory;
    protected $table = 'aml_alerts';
    protected $primaryKey = 'id';
protected $fillable = [
    'transaction_id',
    'transaction_ref',
    'client_id',
    'severity',
    'reason',
    'risk_score',
    'status'
];

}