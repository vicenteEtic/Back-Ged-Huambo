<?php

namespace App\Models\RH\Benefit;

use App\Models\Concerns\HasAutoCode;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\SoftDeletes;

class BenefitType extends Model
{
    use HasFactory, SoftDeletes, HasAutoCode;

    protected static $codePrefix = 'BEN';

    protected $table = 'benefit_types';

    protected $fillable = [
        'name', 'code', 'category', 'description', 'provider',
        'default_amount', 'frequency', 'is_active',
    ];

    protected $casts = ['is_active' => 'boolean'];
}
