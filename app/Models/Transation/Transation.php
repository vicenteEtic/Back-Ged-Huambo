<?php

namespace App\Models\Transation;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Transation extends Model
{
    use HasFactory;
    protected $table = 'transation';
    protected $primaryKey = 'id';
    protected $fillable = ['entite_id', 'amount', 'currency', 'date', 'type', 'status', 'channel', 'description', 'category', 'risk_score', 'ip_address', 'device', 'notes'];
}