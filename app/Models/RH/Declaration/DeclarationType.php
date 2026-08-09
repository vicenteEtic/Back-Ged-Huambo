<?php

namespace App\Models\RH\Declaration;

use App\Enum\DeclarationTypeEnum;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class DeclarationType extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'declaration_types';

    protected $fillable = [
        'code',
        'name',
        'description',
        'requires_approval',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'requires_approval' => 'boolean',
            'is_active' => 'boolean',
        ];
    }

    public function requests()
    {
        return $this->hasMany(DeclarationRequest::class);
    }

    public function getTypeEnumAttribute(): ?DeclarationTypeEnum
    {
        return DeclarationTypeEnum::tryFrom($this->code);
    }
}
