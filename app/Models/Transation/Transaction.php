<?php

namespace App\Models\Transation;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Transaction extends Model
{
    use HasFactory;
    protected $table = 'transaction';
    protected $primaryKey = 'id';
    protected $fillable = ['entity_id', 'amount', 'type', 'transaction_date'];
}