<?php

namespace App\Models\Transation;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Policies extends Model
{
    use HasFactory;
    protected $table = 'policies';
protected $casts = [
    'start_date' => 'datetime',
    'end_date' => 'datetime',
];


    protected $primaryKey = 'id';
    protected $fillable = ['entity_id', 'contract_number', 'product', 'channel', 'agent', 'start_date', 'end_date', 'issue_date', 'renewal_date', 'capital', 'premium_simple', 'premium_total', 'charges', 'interest', 'status'];
}