<?php

namespace App\Models\KYT;

use Illuminate\Database\Eloquent\Model;

class KytRuleProduct extends Model
{
    protected $table = 'kyt_rule_definition_products';

    protected $fillable = [
        'kyt_rule_definition_id',
        'product_name',
        'type',
    ];

    public function rule()
    {
        return $this->belongsTo(KytRule::class, 'kyt_rule_definition_id');
    }
}
