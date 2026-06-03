<?php

namespace App\Models\KYT;

use Illuminate\Database\Eloquent\Model;

class KytRule extends Model
{
    protected $table = 'kyt_rules';

    protected $fillable = [
        'slug',
        'name',
        'entity_type',
        'threshold_field',
        'threshold_value',
        'min_events',
        'max_days',
        'score_base',
        'score_increments',
        'severity',
        'description_template',
        'interpretation_aml',
        'extra_params',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'score_increments' => 'array',
            'extra_params' => 'array',
            'is_active' => 'boolean',
            'threshold_value' => 'float',
        ];
    }

    public function products()
    {
        return $this->hasMany(KytRuleProduct::class);
    }

    public function relevantProducts(): array
    {
        return $this->products
            ->where('type', 'relevant')
            ->pluck('product_name')
            ->all();
    }

    public function excludedProducts(): array
    {
        return $this->products
            ->where('type', 'excluded')
            ->pluck('product_name')
            ->all();
    }
}
